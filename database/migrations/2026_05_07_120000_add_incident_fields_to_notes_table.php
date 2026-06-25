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
        Schema::table('notes', function (Blueprint $table) {
            $table->string('type')->default('general')->after('content');
            $table->foreignUuid('employee_time_record_id')->nullable()->after('user_id')->constrained('employee_time_records')->nullOnDelete();
            $table->index(['type', 'employee_time_record_id'], 'notes_type_time_record_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_type_time_record_idx');
            $table->dropConstrainedForeignId('employee_time_record_id');
            $table->dropColumn('type');
        });
    }
};
