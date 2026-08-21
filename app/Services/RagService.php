<?php

namespace App\Services;

use App\Models\Doctor;
use Illuminate\Support\Facades\Log;
use Throwable;

class RagService
{
    /** Keywords suggesting the question is about a doctor (specialty, reviews, "who is best"). */
    private const DOCTOR_KEYWORDS = [
        'دكتور', 'طبيب', 'دكتورة', 'اختصاص', 'اخصائي', 'أخصائي', 'تخصص',
        'تقييم', 'افضل', 'أفضل', 'مراجعات', 'خبرة',
        'مقترح', 'اسوء', 'احسن',
        'doctor', 'specialist', 'specialty', 'specialization', 'review', 'rating', 'best', 'recommend',
    ];

    /** Keywords suggesting the question is about scheduling/availability (dates, times, slots). */
    private const AVAILABILITY_KEYWORDS = [
        'موعد', 'مواعيد', 'متاح', 'متوفر', 'جدول', 'دوام', 'تاريخ', 'وقت', 'اليوم', 'غدا', 'غداً', 'الاسبوع', 'الأسبوع',
        'بكره',
        'بكرة', 'فاضي',
        'availability', 'available', 'schedule', 'appointment', 'slot', 'date', 'time', 'today', 'tomorrow', 'week',
    ];

    private const IN_SCOPE_KEYWORDS = [
        // ═══ طبي عام - أعراض وحالات ═══
        'الم', 'ألم', 'وجع', 'وجعان', 'موجوع', 'مريض', 'مرض', 'امراض', 'أمراض',
        'صحة', 'صحي', 'دواء', 'ادوية', 'أدوية', 'حبوب', 'علاج', 'معالجة',
        'اعراض', 'أعراض', 'عرض', 'حرارة', 'سخونة', 'حمى', 'دوخة', 'دايخ',
        'صداع', 'راسي', 'رأسي', 'بطني', 'معدة', 'قلب', 'ضغط', 'سكري', 'سكر',
        'تحليل', 'تحاليل', 'فحص', 'فحوصات', 'اشعة', 'أشعة', 'عملية', 'جراحة',
        'قسط', 'اقساط', 'أقساط', 'كسر', 'التهاب', 'حساسية', 'تعب', 'اعياء', 'إعياء',
        'سعال', 'كحة', 'زكام', 'انفلونزا', 'اسهال', 'إسهال', 'امساك', 'إمساك',
        'غثيان', 'قيء', 'استفراغ', 'نزيف', 'جرح', 'حرق', 'لدغة', 'تسمم',
        'حمل', 'حامل', 'ولادة', 'رضاعة', 'تطعيم', 'لقاح', 'تحسس',
        'نتيجة', 'نتائج', 'تقرير', 'تقارير', 'روشتة', 'وصفة طبية', 'تحويل',

        // ═══ دكاترة وتخصصات ═══
        'دكتور', 'طبيب', 'دكتورة', 'طبيبة', 'دكاترة', 'اطباء', 'أطباء',
        'اختصاص', 'اخصائي', 'أخصائي', 'اختصاصي', 'تخصص', 'تخصصات',
        'تقييم', 'تقييمات', 'مراجعة', 'مراجعات', 'رأي', 'اراء', 'آراء',
        'افضل', 'أفضل', 'احسن', 'أحسن', 'خبرة', 'خبرته', 'سيرة',
        'قلبية', 'جلدية', 'اسنان', 'أسنان', 'عظام', 'نساء وولادة', 'اطفال', 'أطفال',
        'باطنية', 'عيون', 'انف واذن وحنجرة', 'نفسية', 'اعصاب', 'أعصاب',
        'جراح', 'ممرض', 'ممرضة', 'سكرتير', 'سكرتيرة', 'عيادة', 'عيادات',

        // ═══ مواعيد وحجوزات ═══
        'موعد', 'مواعيد', 'متاح', 'متوفر', 'فاضي', 'فاضية', 'جدول', 'دوام',
        'حجز', 'احجز', 'أحجز', 'احجزلي', 'بدي احجز', 'الغاء', 'إلغاء', 'الغي',
        'اجل', 'أجل', 'تأجيل', 'تاجيل', 'تعديل موعد', 'تغيير موعد',
        'اليوم', 'بكرا', 'بكرة', 'غدا', 'غداً', 'الاسبوع', 'الأسبوع', 'الشهر',
        'صباح', 'مساء', 'ظهر', 'دوري', 'دور', 'انتظار', 'قائمة انتظار',
        'وقت', 'ساعة', 'تاريخ', 'يوم', 'ايام', 'أيام',

        // ═══ نظام العيادة / حساب ═══
        'حسابي', 'ملفي', 'بياناتي', 'محفظة', 'رصيد', 'دفع', 'فاتورة', 'فواتير',
        'استرجاع', 'استرداد', 'تسجيل', 'دخول', 'خروج', 'باسورد', 'كلمة مرور',
        'شكوى', 'شكاوي', 'شكاوى', 'اقتراح', 'مساعدة', 'دعم',

        // ═══ محادثة عامة مقبولة (تحية/شكر/بداية حديث) ═══
        'مرحبا', 'مرحباً', 'أهلا', 'اهلا', 'أهلين', 'اهلين', 'هاي', 'هلا',
        'شكرا', 'شكراً', 'يعطيك العافية', 'تسلم', 'السلام عليكم', 'وعليكم السلام',
        'كيفك', 'كيفكم', 'شلونك', 'ايش اخبارك', 'إيش اخبارك', 'ازيك', 'أزيك',
        'صباح الخير', 'مساء الخير', 'تصبح على خير', 'مع السلامة', 'باي',
        'ممكن', 'بدي', 'محتاج', 'محتاجة', 'عايز', 'عايزة', 'ابغى', 'أبغى',
        'سؤال', 'استفسار', 'اسأل', 'أسأل', 'اعرف', 'أعرف',

        // ═══ English - medical ═══
        'doctor', 'physician', 'clinic', 'appointment', 'schedule', 'booking',
        'pain', 'hurt', 'symptom', 'symptoms', 'medicine', 'medication', 'treatment',
        'specialist', 'specialty', 'specialization', 'diagnosis', 'prescription',
        'health', 'healthy', 'sick', 'illness', 'disease', 'fever', 'headache',
        'checkup', 'test', 'results', 'report', 'surgery', 'blood', 'pressure',
        'diabetes', 'allergy', 'cough', 'cold', 'flu', 'nausea', 'vomit',
        'pregnant', 'pregnancy', 'vaccine', 'vaccination', 'nurse', 'cancel',
        'reschedule', 'available', 'availability', 'slot', 'today', 'tomorrow',
        'review', 'rating', 'best', 'recommend', 'recommendation', 'experience',

        // ═══ English - general/greeting ═══
        'hi', 'hello', 'hey', 'thanks', 'thank you', 'please', 'help',
        'good morning', 'good evening', 'bye', 'goodbye', 'question', 'ask',
        'account', 'profile', 'wallet', 'payment', 'invoice', 'refund',
        'login', 'password', 'complaint', 'support',
    ];

    public function __construct(
        private readonly DoctorVectorSearchService $vectorSearch,
        private readonly SqlRetrievalService $sqlRetrieval,
        private readonly GeminiChatService $chatService,
    ) {}

    /**
     * Produce the final AI reply for a user's question.
     *
     * @param  string  $question  The raw message the patient just sent.
     * @param  array  $chatHistory  Last messages (Gemini role/parts format), oldest first.
     */
    public function answer(string $question, array $chatHistory): string
    {
        if (! $this->isInScope($question)) {
            return 'أنا مساعد طبي بعيادة Medics، بقدر أساعدك بأسئلة متعلقة بصحتك، الأطباء، أو مواعيدك بس 🙂';
        }

        $context = $this->buildContext($question);

        try {
            return $this->chatService->generateReply($chatHistory, $context);
        } catch (Throwable $e) {
            Log::error('RAG: final Gemini answer generation failed', ['error' => $e->getMessage()]);

            return 'عذراً، حدث خطأ مؤقت أثناء معالجة سؤالك. الرجاء المحاولة مرة أخرى بعد قليل.';
        }
    }

    private function isInScope(string $question): bool
    {
        $normalized = mb_strtolower($question);

        return $this->containsAnyKeyword($normalized, self::IN_SCOPE_KEYWORDS);
    }

    private function containsAnyKeyword(string $haystack, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($haystack, mb_strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }

    private function buildContext(string $question): string
    {
        [$useVectorSearch, $useSqlSearch] = $this->route($question);

        $contextParts = [];

        if ($useVectorSearch) {
            $doctorContext = $this->safelyBuildDoctorContext($question);
            if ($doctorContext !== '') {
                $contextParts[] = $doctorContext;
            }
        }

        if ($useSqlSearch) {
            $sqlContext = $this->safelyBuildSqlContext($question);
            if ($sqlContext !== '') {
                $contextParts[] = $sqlContext;
            }
        }

        return implode("\n\n", $contextParts);
    }

    /**
     * @return array{0: bool, 1: bool} [useVectorSearch, useSqlSearch]
     */
    private function route(string $question): array
    {
        $normalized = mb_strtolower($question);

        $isDoctorQuestion = $this->containsAnyKeyword($normalized, self::DOCTOR_KEYWORDS);
        $isAvailabilityQuestion = $this->containsAnyKeyword($normalized, self::AVAILABILITY_KEYWORDS);

        if (! $isDoctorQuestion && ! $isAvailabilityQuestion) {
            return [true, true];
        }

        return [$isDoctorQuestion, $isAvailabilityQuestion];
    }

    private function safelyBuildDoctorContext(string $question): string
    {
        try {
            $doctors = $this->vectorSearch->findRelevantDoctors($question, 3);
        } catch (Throwable $e) {
            Log::warning('RAG: doctor vector search failed', ['error' => $e->getMessage()]);

            return '';
        }

        if ($doctors->isEmpty()) {
            return '';
        }

        $lines = ['Relevant doctors found (semantic search over specialization, bio and reviews):'];

        foreach ($doctors as $doctor) {
            $lines[] = $this->describeDoctor($doctor);
        }

        return implode("\n", $lines);
    }

    private function describeDoctor(Doctor $doctor): string
    {
        $name = trim(($doctor->user->first_name ?? '').' '.($doctor->user->last_name ?? ''));
        $averageRating = $doctor->reviews()->avg('rating');

        $ratingText = $averageRating
            ? sprintf(', average rating %.1f/5 (%d reviews)', $averageRating, $doctor->reviews()->count())
            : ', no reviews yet';

        return sprintf(
            '- Dr. %s, specialization: %s, %d years of experience%s.',
            $name !== '' ? $name : 'Unknown',
            $doctor->specialization->value,
            $doctor->years_of_experience,
            $ratingText
        );
    }

    private function safelyBuildSqlContext(string $question): string
    {
        $rows = $this->sqlRetrieval->retrieve($question);

        if ($rows === null) {
            return 'I could not find precise scheduling data for this question, so give a general, helpful answer instead.';
        }

        if (empty($rows)) {
            return 'The database query for this question returned no matching rows.';
        }

        return "Data retrieved from the clinic's database for this question:\n".json_encode($rows,
            JSON_UNESCAPED_UNICODE);
    }
}
