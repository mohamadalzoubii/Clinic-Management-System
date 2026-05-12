<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {

        Schema::table('conversations', function (Blueprint $table) {
            $table->foreignId('doctor_id')->nullable()->change();
            $table->boolean('is_ai')->default(false)->after('doctor_id');
        });

        Schema::table('messages', function (Blueprint $table) {
            $table->foreignId('sender_user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->boolean('is_ai')->default(false);
        });
    }
};
