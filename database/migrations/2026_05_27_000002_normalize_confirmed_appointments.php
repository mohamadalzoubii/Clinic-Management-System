<?php

use App\Enums\Medical\AppointmentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('appointments')
            ->where('status', 'confirmed')
            ->update(['status' => AppointmentStatus::PENDING->value]);
    }

    public function down(): void
    {
        DB::table('appointments')
            ->where('status', AppointmentStatus::PENDING->value)
            ->update(['status' => 'confirmed']);
    }
};
