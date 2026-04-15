<?php

namespace App\DTOs\prescrionItem;

use Illuminate\Http\Request;

class PrescriptionItemDTO
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        public readonly string $name,
        public readonly string $dosage,
        public readonly string $duration,
        public readonly string $frequency,
        public readonly string $itemNotes,
    ) {}

    public static function fromArray(array $data):self {
        //هون كنت عم تسال ليش ما استخدم الريكوست واستخدم ارييه + انت لسا ما عملت للسجل الطبي تبع المواعيد 
        //dto
        //كمان هدفك تعمل  الاكشن تبع الي بتخلي الدكتور يكتب ملاحظات ويحدد موعد جلسه ثانيه ويوصف دواا 
        // وبعدها هدفك كمان انك تعمل اشعارات تذكير قبل الموعد بفتره وكمان انك تعمل الشات والله االمستعان على كل شي
       return new self(
            name: $data['name'],
            dosage: $data['dosage'],
            frequency: $data['frequency'],
            duration: $data['duration'],
            itemNotes: $data['item_notes'] ?? null,
        );
    }
}
