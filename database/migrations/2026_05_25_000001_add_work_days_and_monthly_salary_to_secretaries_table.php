<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('secretaries', function (Blueprint $table) {
            $table->string('work_days')->nullable()->after('user_id');
            $table->decimal('monthly_salary', 10, 2)->nullable()->after('work_days');
        });
    }

    public function down(): void
    {
        Schema::table('secretaries', function (Blueprint $table) {
            $table->dropColumn(['work_days', 'monthly_salary']);
        });
    }
};