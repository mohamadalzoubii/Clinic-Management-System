<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('city')->nullable()->after('phone');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->string('city')->nullable()->after('user_id');
            $table->string('emergency_contact_relationship')->nullable()->after('emergency_contact_phone');
            $table->string('emergency_contact_email')->nullable()->after('emergency_contact_relationship');
            $table->string('emergency_contact_city')->nullable()->after('emergency_contact_email');
            $table->string('smoking_status')->nullable()->after('is_smoker');
            $table->string('alcohol_status')->nullable()->after('drinks_alcohol');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('city');
        });

        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'city',
                'emergency_contact_relationship',
                'emergency_contact_email',
                'emergency_contact_city',
                'smoking_status',
                'alcohol_status',
            ]);
        });
    }
};
