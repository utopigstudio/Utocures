<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('time_record_mode', 20)->default('work');
        });
        Schema::table('employee_time_records', function (Blueprint $table) {
            $table->foreignUuid('assigned_hour_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if (DB::table('employee_time_records')->whereNull('assigned_hour_id')->exists()) {
            throw new RuntimeException('Cannot roll back while administrative time records exist.');
        }
        Schema::table('employee_time_records', function (Blueprint $table) {
            $table->foreignUuid('assigned_hour_id')->nullable(false)->change();
        });
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('time_record_mode');
        });
    }
};
