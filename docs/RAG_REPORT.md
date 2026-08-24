# RAG Layer — Graduation Committee Report

This document explains the Retrieval-Augmented Generation (RAG) layer added to the
existing AI chatbot, in plain language, so it can be defended in front of the
grading committee.

## 1. What "RAG" means here, in one sentence

Instead of asking Gemini to answer purely from its own general knowledge, we first
**retrieve** real facts from our own database (which doctor fits the question, what
their schedule looks like), and **hand those facts to Gemini as extra context**
before it writes the final answer. That's the whole idea — retrieval, then generation.

## 2. End-to-end flow, step by step

A patient sends a message through the existing `POST /ai-chat/send` endpoint. Nothing
changed about the endpoint or the frontend — only what happens *inside*
`SendAiMessageAction` changed.

```
Patient message
      │
      ▼
1. SendAiMessageAction saves the message, then calls RagService::answer()
      │
      ▼
2. ROUTING (RagService::route) — plain PHP keyword matching, no AI call.
   Looks at the question for two keyword lists:
     - doctor-related words  ("دكتور", "تخصص", "أفضل", "review", "specialist"...)
     - schedule-related words ("موعد", "متاح", "اليوم", "schedule", "available"...)
   Decides which retrieval path(s) below to run.
      │
      ├─── path (a) VECTOR SEARCH ──────────────────────────────┐
      │    Only runs if doctor-keywords matched (or unclear).   │
      │    1. Embed the question with Gemini (gemini-embedding-001)
      │    2. Compare it (cosine similarity, plain PHP) against  │
      │       every doctor's pre-computed embedding              │
      │    3. Take the top 2-3 doctors                           │
      │                                                           │
      ├─── path (b) SQL SEARCH ─────────────────────────────────┤
      │    Only runs if schedule-keywords matched (or unclear). │
      │    1. Send the question + a restricted schema (only     │
      │       appointments/doctors/doctor_schedules) to Gemini   │
      │    2. Gemini writes ONE SELECT query                     │
      │    3. Validate it (SELECT-only, allowed tables only)     │
      │    4. Run it, take the rows                              │
      │                                                           │
      └─── if the question is ambiguous → BOTH paths run ────────┘
      │
      ▼
3. CONTEXT ASSEMBLY — the doctor matches and/or SQL rows are turned into a
   short block of plain text ("Relevant doctors found: ...", "Data retrieved: ...")
      │
      ▼
4. FINAL ANSWER — that context + the last 5 chat messages (existing behavior,
   unchanged) are sent to Gemini (gemini-2.5-flash) as before. The context is
   injected into the system instruction, so Gemini blends it into a natural
   answer instead of just reciting raw data.
      │
      ▼
5. The reply is saved as a normal AI message and returned to the patient —
   same response shape as before RAG was added.
```

## 3. Why hybrid (vector + SQL), not just one

These two retrieval paths solve two *different* kinds of question, and neither one
can answer the other's question well:

- **Vector/semantic search** answers *fuzzy, meaning-based* questions like "best
  doctor for chest pain" or "which cardiologist has good reviews". There's no exact
  column to filter on — you need to understand what "chest pain" *means* and match
  it against doctor bios/specializations/reviews. This is what embeddings are for.
- **SQL retrieval** answers *exact, factual* questions like "does Dr. X have a slot
  on Sunday" or "how many confirmed appointments does each doctor have". These need
  precise database lookups — an embedding can't tell you an exact time slot, only a
  SQL query can.

Using only one path would leave the other type of question badly served: pure SQL
generation can't rank doctors by "who's best for X", and pure vector search can't
tell you exact appointment counts or open slots. Combining both, chosen deterministically
per question, covers the two question types the clinic's patients actually ask.

## 4. Why rule-based routing, not an extra Gemini call

Routing is done with a simple PHP function (`RagService::route`) that checks the
question for two keyword lists — no LLM call is involved in choosing the path.

This was a deliberate choice, not a shortcut taken because a smarter method wasn't
known:

- **Predictability during a live demo.** A keyword match is 100% deterministic — the
  same question always routes the same way. An LLM-based router could occasionally
  misclassify a question in front of the committee, and that failure would be much
  harder to explain live than "the word 'موعد' wasn't in the question."
- **Speed and cost.** It's an instant, free PHP function instead of a third Gemini
  API call on every single message (on top of the SQL-generation call and the
  final-answer call that may already happen).
- **It's trivial to explain.** I can point to the two keyword arrays and the
  three-line `if` statement and explain the exact behavior for any question the
  committee throws at me, including the fallback rule: **if a question matches
  neither list (or matches both), both retrieval paths run**, so ambiguous
  questions don't lose context — they just cost one extra retrieval call instead of
  a wrong routing decision.

## 5. Safety measures around SQL generation, and why they matter

The SQL path is the highest-risk part of this feature, because it lets an LLM write
a database query. Three independent layers protect against that risk
(`app/Services/SqlRetrievalService.php`):

1. **Schema restriction** — the prompt sent to Gemini only ever describes three
   tables: `appointments`, `doctors`, `doctor_schedules` (table/column names only,
   zero real data). Tables like `users`, `patients`, `invoices` — which hold emails,
   password hashes, and wallet balances — are never mentioned to the model at all.
   In testing, this alone was enough: when asked to fetch user emails/passwords,
   Gemini correctly responded `NONE` because it has no idea those tables exist.
2. **SELECT-only + keyword validation** — before any generated query touches the
   database, it must: be a single statement (no `;` mid-query), start with `SELECT`,
   and contain none of `INSERT/UPDATE/DELETE/DROP/ALTER/TRUNCATE/CREATE/...`. This
   is checked in PHP with `preg_match`, not by trusting Gemini's own instructions.
3. **Table allow-list enforcement** — every `FROM`/`JOIN` target in the generated
   query is extracted with a regex and checked against the same three-table
   allow-list. A query referencing any other table is rejected outright, even if it
   is a syntactically valid, harmless-looking SELECT.

A row limit (`LIMIT 20`) is also appended if the generated query doesn't already
have one, so the context handed to the final Gemini call always stays small and
predictable.

These layers matter because layer 1 (schema restriction) is what stops Gemini from
*trying* to touch sensitive data in the first place, but it relies on Gemini
following instructions. Layers 2 and 3 are the actual enforcement — they don't trust
Gemini's judgment at all, they mechanically re-check the output before it's allowed
anywhere near the database.

## 6. What happens when something fails (this is by design, not a bug)

Every external Gemini call (embedding generation, SQL generation, final answer) is
wrapped in `try/catch`. Nothing in this pipeline can throw an uncaught exception up
to the HTTP layer:

- If **embedding generation** fails → `DoctorVectorSearchService` returns an empty
  result → no doctor context is added, the question still gets answered generally.
- If **SQL generation or validation fails** (bad query, disallowed table, Gemini
  timeout) → `SqlRetrievalService::retrieve()` returns `null` → the context becomes
  "I could not find precise scheduling data for this question, so give a general,
  helpful answer instead" → Gemini still answers, just without exact numbers.
- If **the final answer call itself fails** (timeout, rate limit, API error) →
  `RagService::answer()` catches it and returns a fixed, friendly Arabic message:
  *"عذراً، حدث خطأ مؤقت أثناء معالجة سؤالك..."* instead of an exception.
- As a last resort, `SendAiMessageAction` has its own outer `try/catch` around the
  whole `RagService` call, so even a bug I didn't anticipate still returns a
  friendly message instead of a raw 500 error on screen.

**In short: if the committee sees a generic "please try again" reply instead of a
detailed answer, that is the fallback working correctly, not the system crashing.**

## 7. Files added / changed

| File                                                                       | Purpose                                                                                                                                                                                                              |
|----------------------------------------------------------------------------|----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| `database/migrations/2026_08_18_202238_add_embedding_to_doctors_table.php` | Adds nullable `embedding` JSON column to `doctors`.                                                                                                                                                                  |
| `app/Models/Doctor.php`                                                    | Casts `embedding` to array; adds `toEmbeddingText()` (specialization + bio + review summary → text for embedding). `embedding` deliberately kept out of `$fillable` so it can never be set via a normal API request. |
| `app/Services/GeminiEmbeddingService.php`                                  | Calls Gemini's `embedContent` endpoint (`gemini-embedding-001`) to turn text into a vector. Used both by the artisan command and by query-time search.                                                               |
| `app/Services/DoctorVectorSearchService.php`                               | Embeds the question, computes cosine similarity in PHP against every doctor's stored embedding, returns the top matches. No external vector DB.                                                                      |
| `app/Services/SqlRetrievalService.php`                                     | Restricted schema description, Gemini SQL generation, SELECT-only + allow-list validation, safe execution with row limit.                                                                                            |
| `app/Services/RagService.php`                                              | Orchestrator: rule-based routing, calls the two retrieval services, assembles context, calls `GeminiChatService` for the final answer, catches all failures.                                                         |
| `app/Services/GeminiChatService.php`                                       | *(modified)* `generateReply()` now accepts an optional `$ragContext` string, appended to the system instruction. Behavior is unchanged when no context is passed.                                                    |
| `app/Actions/Chat/SendAiMessageAction.php`                                 | *(modified)* Now calls `RagService::answer()` instead of `GeminiChatService::generateReply()` directly; adds an outer try/catch safety net.                                                                          |
| `app/Console/Commands/GenerateDoctorEmbeddings.php`                        | `php artisan doctors:generate-embeddings [--doctor=ID]` — manually (re)generates embeddings. Not run automatically on every request.                                                                                 |
| `config/services.php`                                                      | *(modified)* Adds `services.gemini.embedding_model` (default `gemini-embedding-001`, overridable via `GEMINI_EMBEDDING_MODEL` in `.env`).                                                                            |
| `RAG_REPORT.md`                                                            | This document.                                                                                                                                                                                                       |

No frontend files, routes, or controllers changed — the existing `/ai-chat/send`
and `/ai-chat/history` endpoints and their request/response shapes are untouched.

## 8. Likely committee questions

**Q: Why not use a real vector database (Pinecone, pgvector, etc.)?**
A: The clinic has a small, slowly-changing number of doctors (a dozen or so).
Looping through them and computing cosine similarity in PHP is fast enough (a few
milliseconds) and needs zero extra infrastructure. A vector database would add
real complexity for a dataset this size, and would be a solution to a scaling
problem this project doesn't have.

**Q: Why isn't routing done by an LLM — wouldn't that be "smarter"?**
A: It might classify slightly better on rare edge cases, but it trades away
determinism, speed, and an extra point of failure — for a live demo, a routing
decision I can predict and explain beats one an LLM might handle inconsistently.
When a question is ambiguous, my rule-based router doesn't guess — it just runs
both retrieval paths, so nothing is lost by not "understanding" the question first.

**Q: What stops the SQL-generation step from leaking patient emails or wallet
balances?**
A: Two independent things. First, the schema description sent to Gemini never
mentions `users`, `patients`, or `invoices` — Gemini can't reference a table it
was never told exists. Second, even if it tried, every generated query is
mechanically checked in PHP against an allow-list of exactly three tables
(`appointments`, `doctors`, `doctor_schedules`) before it's ever executed, plus a
SELECT-only keyword check. Both were tested directly (see `SqlRetrievalService`
validator unit-tested against `SELECT * FROM users`, `DROP TABLE`, `DELETE FROM`,
etc. — all correctly rejected).

**Q: What happens if Gemini is down or slow during the demo?**
A: Every Gemini call is wrapped in try/catch with retries already built into the
existing `Http` client config (3 retries, 2s apart). If it still fails, the user
gets a short, friendly Arabic fallback message instead of an error page — the
chat never crashes or shows a broken response.

**Q: Why store the embedding as a JSON column instead of a dedicated vector type?**
A: MySQL (this project's database) doesn't have a native vector column type the
way Postgres+pgvector does, and adding one would mean a new database extension for
a dataset of a dozen doctors. A JSON array of floats, decoded in PHP, is simple,
portable, and easy to inspect directly in the database — which matters for being
able to explain and debug it live.
