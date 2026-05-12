<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('consultation_id')->constrained('consultations')->cascadeOnDelete();

            $table->string('medicine_name');
            $table->string('category')->nullable();
            $table->string('dosage');
            $table->string('form_and_quantity')->nullable();
            $table->string('duration');
            $table->string('frequency');

            $table->text('special_instructions')->nullable();
            $table->text('storage_instructions')->nullable();
            $table->text('side_effects')->nullable();
            $table->text('allergy_warnings')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
