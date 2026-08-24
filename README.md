# Medics

A Laravel-based appointment booking and management system for a medical center, built as a university graduation project.

## Overview

Medics is a REST API that lets patients discover doctors, book and manage appointments, and chat with an AI medical assistant, while doctors, secretaries, and admins manage schedules, consultations, vacations, invoices, and reviews from their own role-scoped endpoints. Authentication and authorization are handled with Laravel Sanctum and role-based middleware (admin, doctor, patient, secretary), and the system includes real-time chat (Laravel Reverb/Pusher) alongside a Gemini-powered AI assistant.

## Features

- **Role-based access control** — Admin, Doctor, Patient, and Secretary roles authenticated via Laravel Sanctum, with routes gated by `role:` middleware (`spatie/laravel-permission`). Each role has its own login endpoint and its own set of accessible routes.
- **Appointment booking & management** — patients book, cancel, and reschedule appointments; doctors can mark no-shows or cancel; admins can view/delete appointments across the clinic.
- **Doctor schedules & availability** — versioned doctor schedules (`DoctorScheduleVersion` / `DoctorScheduleVersionItem`), available-days/available-slots lookups, and vacation management (request/approve/decline/drop) for both doctors and admins.
- **Emergency booking (Secretary)** — a dedicated `secretary/*` route group (`SecretaryEmergencyController`) lets front-desk secretaries list doctors, check a doctor's available/blocked slots for a given date, block a slot, and create an emergency booking (`emergency-book`) outside the normal patient booking flow.
- **Doctor review system** — patients can submit a rating + comment for a doctor (`POST /doctors/{doctor}/reviews`) and list a doctor's reviews (`GET /doctors/{doctor}/reviews`); admins can also view a doctor's reviews.
- **Consultations & prescriptions** — doctors record consultations (anamnesis, symptoms, diagnosis, next visit date) and prescription items per appointment.
- **Patient medical media** — upload/retrieve X-rays and medical test files per patient (via Spatie Media Library).
- **Billing** — invoices, wallet charging, package purchases (patient packages), and PDF invoice downloads (`barryvdh/laravel-dompdf`).
- **Real-time & AI chat**:
  - Doctor↔Patient real-time messaging (`ChatController`, `MessageSent` broadcast event over Reverb/Pusher).
  - An **AI chatbot** (`AiChatController` → `RagService`) that acts as a hybrid RAG system:
    - **Semantic search over doctors**: doctor bios/specialization/reviews are embedded offline via a `doctors:generate-embeddings` Artisan command (Gemini `gemini-embedding-001`) and stored on `doctors.embedding`. When a user asks a doctor-related question ("who is the best cardiologist?", "أفضل دكتور جلدية؟"), the question is embedded the same way and compared against stored doctor vectors using cosine similarity (`DoctorVectorSearchService`) to retrieve the most relevant doctors.
    - **SQL-RAG for numeric/scheduling questions**: questions about availability, dates, or appointment counts are routed to `SqlRetrievalService`, which queries the database directly for precise, structured answers.
    - Both retrieval paths are combined (or run together when intent is ambiguous) into a context block that is passed to **Gemini (`gemini-2.5-flash`)** via `GeminiChatService`, which generates the final reply — always identifying itself as an AI assistant, not a real doctor.
    - Out-of-scope questions (unrelated to health/clinic topics) are filtered with a keyword-based scope check before any model call is made.

## Tech Stack

**Backend**
- PHP ^8.2, Laravel ^12.0
- Laravel Sanctum ^4.0 — API authentication
- Spatie Laravel Permission ^6.25 — roles/permissions
- Spatie Laravel MediaLibrary ^11.23 — file/media uploads
- Laravel Reverb ^1.10 + Pusher PHP Server ^7.2 — real-time broadcasting
- Barryvdh Laravel DomPDF ^3.1 — PDF invoice generation
- SimpleSoftwareIO Simple QRCode ^4.2
- Google Gemini API (`gemini-2.5-flash` for chat, `gemini-embedding-001` for embeddings) — called directly over HTTP, no SDK dependency in composer.json

**Frontend build tooling** (asset pipeline only, per `package.json`)
- Vite ^7.0.7 with `laravel-vite-plugin` ^2.0.0
- Tailwind CSS ^4.0.0 (`@tailwindcss/vite`)
- Axios ^1.11.0

**Dev/Test**
- PHPUnit ^11.5.3, Mockery ^1.6, FakerPHP ^1.23
- Laravel Pint (code style), Laravel Sail, Laravel Pail (log viewer)

## Project Structure

A simplified view of `app/`, organized around a Service / Action / DTO pattern (controllers stay thin, business logic lives in Services and single-purpose Actions, and validated request data is passed around as DTOs):

```
app/
├── Actions/                # Single-purpose, invokable use-cases
│   ├── Admin/               # e.g. GetDoctorSchedulesAction, UpdateDoctorScheduleAction
│   ├── AI/                  # GetAiChatHistoryAction
│   ├── Appointment/         # GetAppointmentsAction
│   ├── Auth/                 # LoginAction, RegisterAction, LogoutAction
│   ├── Chat/                 # SendMessageAction, SendAiMessageAction, GetDoctorThreadsAction
│   └── Medical/
│       ├── Consutation/       # Consultation-related actions
│       ├── Doctor/            # GetDoctorAvailableDaysAction, GetDoctorAvailableSlotsAction
│       ├── Patient/           # Patient-related actions
│       ├── Review/            # StoreDoctorReviewAction
│       ├── Secretary/         # CreateEmergencyBookingAction, BlockSlotAction, GetAllSlotsForSecretaryAction
│       └── Stats/             # Dashboard/stat actions
├── Console/Commands/        # e.g. GenerateDoctorEmbeddings, AutoCancelExpiredAgendaAppointments
├── DTOs/                    # Typed data-transfer objects per domain (Auth, Appointment, Doctor, Review, Chat, Stats, ...)
├── Enums/                   # RoleEnum, OtpType, UserStatus, Medical/* (AppointmentStatus, DoctorSpecialization, ...)
├── Events/                  # MessageSent (broadcast)
├── Exceptions/              # BusinessLogicException
├── Http/
│   ├── Controllers/Api/V1/  # Versioned API controllers, grouped by role (Admin/, Doctor/, Secretary/)
│   ├── Filters/V1/          # Query filtering (Filterable trait)
│   ├── Requests/            # Form Request validation, grouped by domain
│   └── Resources/Api/V1/    # API Resource transformers
├── Mail/                    # SendOtpMail
├── Models/                  # Eloquent models (User, Doctor, Patient, Appointment, DoctorReview, Invoice, ...)
├── Notifications/           # AppointmentReminder, NewMessageNotification
├── Policies/                # ChatPolicy, UserPolicy
├── Services/                 # Core business/domain services
│   ├── Admin/                 # DashboardService, DoctorService
│   ├── Medical/                # DoctorScheduleVersionService
│   ├── GeminiChatService.php    # Gemini generateContent wrapper
│   ├── GeminiEmbeddingService.php # Gemini embedContent wrapper
│   ├── DoctorVectorSearchService.php # Cosine-similarity semantic search over doctors
│   ├── SqlRetrievalService.php    # SQL-RAG retrieval for numeric/scheduling questions
│   ├── RagService.php             # Orchestrates scope-check + routing + Gemini reply
│   ├── InvoiceService.php, VacationService.php, TimeSlotGeneratorService.php, ...
└── Traits/                   # ApiResponses, Filterable
```

## Setup

1. **Clone and install PHP dependencies**
   ```bash
   composer install
   ```

2. **Environment file** — copy `.env.example` to `.env` and fill in your local values:
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   At minimum, configure:
   - `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` (MySQL, per `.env.example`)
   - `GEMINI_API_KEY` (required for the AI chatbot — used by `GeminiChatService` and `GeminiEmbeddingService`; not present in `.env.example` by default, must be added manually)
   - Mail (`MAIL_*`) settings if you want OTP emails (`SendOtpMail`) to actually send
   - Broadcasting/queue settings if you want real-time chat (Reverb/Pusher) and queued jobs to work

3. **Database**
   ```bash
   php artisan migrate
   ```

4. **(Optional) Generate doctor embeddings** for the AI doctor-search feature:
   ```bash
   php artisan doctors:generate-embeddings
   ```

5. **Install frontend build tooling** (Vite/Tailwind, for asset compilation):
   ```bash
   npm install
   npm run dev    # or: npm run build
   ```

6. **Serve the app**
   ```bash
   php artisan serve
   ```
   Or run the full local dev stack (server + queue listener + log viewer + Vite) in one command:
   ```bash
   composer run dev
   ```

## API Structure

All endpoints are prefixed under `/api` (see `routes/api_v1.php`) and, except login/register, require a Sanctum bearer token. Below is a simplified grouping by role — TODO: generate a full endpoint reference (e.g. via `php artisan route:list`) for exhaustive coverage.

| Group | Example Routes | Notes |
|---|---|---|
| **Public / Auth** | `POST /register`, `POST /verify-otp`, `POST /login`, `POST /doctors/login`, `POST /admin/login`, `POST /secretary/login`, `POST /logout` | Separate login endpoints per role |
| **Public (unauthenticated read)** | `GET /doctors`, `GET /doctors/{doctor}`, `GET /doctors/specializations`, `GET /doctors/{doctor}/available-days`, `GET /appointments`, `GET /appointments/{appointment}`, `GET /appointments/available-slots` | Listed inside the `auth:sanctum` group in the route file but not further role-restricted |
| **Patient** (`role:patient`) | `POST /patient/CompleteProftile`, `PUT /patient/updateprofile/{patient}`, `GET /patient/appointments`, `GET /patient/wallet-balance`, `GET /patient/prescriptions`, `POST /appointments/storeAppointment`, `PATCH /appointments/{appointment}/update`, `PATCH /appointments/{appointment}/reschedule`, `POST /doctors/{doctor}/reviews`, `GET /doctors/{doctor}/reviews`, `GET /patient/chat/doctors-threads` | |
| **Doctor** (`role:doctor`) | `GET/POST /doctor/vacations`, `POST /doctor/media/xrays`, `POST /doctor/media/medical-tests`, `GET /doctor/chat/patients-threads`, `PATCH /appointments/{appointment}/no-show`, `PATCH /appointments/{appointment}/cancel` | |
| **Shared** (`role:admin\|doctor\|secretary`) | `GET /patients`, `GET /patients/{patient}`, `GET /doctors/dashboard`, `GET /doctors/summary`, `GET /available-days`, `GET /available-slots`, `GET /doctor/agenda`, `POST /appointments/{appointment}/consultations` | |
| **Admin** (`role:admin`, prefix `/admin`) | `GET /admin/overview`, CRUD on `/admin/doctors`, `/admin/patients`, `/admin/packages`, `/admin/secretaries`, `GET /admin/invoices`, `GET/PUT /admin/doctors/{doctor}/schedules`, CRUD-ish on `/admin/vacations` (approve/decline/drop), `GET/DELETE /admin/appointments` | |
| **Secretary — Emergency** (`auth:sanctum`, prefix `/secretary`) | `GET /secretary/doctors`, `GET /secretary/doctors/count`, `GET /secretary/doctors/{doctor}/available-slots`, `GET /secretary/doctors/{doctor}/available-days`, `GET /secretary/doctors/{doctor}/all-slots`, `POST /secretary/block-slot`, `POST /secretary/emergency-book` | Emergency booking feature |
| **Secretary** (`role:secretary`, prefix `/secretary`) | `POST /secretary/patients/login-as-patient`, CRUD-ish on `/secretary/patients`, `GET /secretary/appointments/today`, `GET /secretary/doctors/{doctor}/agenda` | |
| **Chat** | `POST /chat/sendmessages`, `GET /chat/{receiverId}/getmessages` | Real-time doctor↔patient chat |
| **AI Chat (RAG)** | `POST /ai-chat/send`, `GET /ai-chat/history` | See Features section above |
| **Financial** | `GET /invoices`, `POST /invoices/wallet/charge`, `GET /invoices/{invoice}/download`, `GET /invoices/emergency/list` | |
| **Packages** (`role:admin\|secretary\|patient`) | `GET /packages`, `GET /packages/{package}` | Admin-only CRUD is under `/admin/packages` |

## Database Schema

See [`database_schema.md`](./database_schema.md) for the full entity-relationship diagram.

## License

TODO — `composer.json` inherits the default `laravel/laravel` skeleton license (MIT), but no project-specific `LICENSE` file exists in this repository. Confirm the intended license with the project author before publishing.
