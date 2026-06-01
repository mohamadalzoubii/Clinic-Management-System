<?php

use App\Enums\Medical\ScheduleStatus;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('doctor_schedule_version_items');
        Schema::dropIfExists('doctor_schedule_versions');

        Schema::create('doctor_schedule_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
            $table->date('effective_from_date');
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['doctor_id', 'effective_from_date'], 'doc_sched_versions_doc_date_idx');
        });

        Schema::create('doctor_schedule_version_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_schedule_version_id')->constrained('doctor_schedule_versions')->cascadeOnDelete();
            $table->string('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('slot_duration')->default(30);
            $table->string('status')->default(ScheduleStatus::ACTIVE->value);
            $table->timestamps();

            $table->index(['doctor_schedule_version_id', 'day_of_week'], 'doc_sched_version_items_ver_day_idx');
        });

        $today = Carbon::today()->toDateString();
        $doctorIds = DB::table('doctor_schedules')
            ->where('status', ScheduleStatus::ACTIVE->value)
            ->distinct()
            ->pluck('doctor_id');

        foreach ($doctorIds as $doctorId) {
            $activeSchedules = DB::table('doctor_schedules')
                ->where('doctor_id', $doctorId)
                ->where('status', ScheduleStatus::ACTIVE->value)
                ->get();

            if ($activeSchedules->isEmpty()) {
                continue;
            }

            $versionId = DB::table('doctor_schedule_versions')->insertGetId([
                'doctor_id' => $doctorId,
                'effective_from_date' => $today,
                'created_by_admin_id' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $versionItems = $activeSchedules->map(function ($schedule) use ($versionId) {
                return [
                    'doctor_schedule_version_id' => $versionId,
                    'day_of_week' => $schedule->day_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'slot_duration' => $schedule->slot_duration,
                    'status' => $schedule->status ?? ScheduleStatus::ACTIVE->value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            DB::table('doctor_schedule_version_items')->insert($versionItems);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('doctor_schedule_version_items');
        Schema::dropIfExists('doctor_schedule_versions');
    }
};