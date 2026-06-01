<?php

use App\Enums\Medical\VacationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('doctor_id')->constrained()->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status')->default(VacationStatus::PENDING->value);
            $table->string('submitted_by')->default('doctor');
            $table->timestamps();

            $table->index(['doctor_id', 'status']);
            $table->index(['doctor_id', 'start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacations');
    }
};