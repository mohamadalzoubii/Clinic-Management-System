<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->string('entry_type')->nullable()->after('invoice_number');
        });

        DB::statement('ALTER TABLE invoices DROP FOREIGN KEY invoices_appointment_id_foreign');
        DB::statement('ALTER TABLE invoices MODIFY appointment_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_appointment_id_foreign FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE');

        Schema::table('invoices', function (Blueprint $table) {
            $table->unique('invoice_number');
            $table->unique(['appointment_id', 'entry_type'], 'invoices_appointment_entry_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropUnique('invoices_invoice_number_unique');
            $table->dropUnique('invoices_appointment_entry_type_unique');
            $table->dropColumn('entry_type');
        });

        DB::statement('ALTER TABLE invoices DROP FOREIGN KEY invoices_appointment_id_foreign');
        DB::statement('ALTER TABLE invoices MODIFY appointment_id BIGINT UNSIGNED NOT NULL');
        DB::statement('ALTER TABLE invoices ADD CONSTRAINT invoices_appointment_id_foreign FOREIGN KEY (appointment_id) REFERENCES appointments (id) ON DELETE CASCADE');
    }
};
