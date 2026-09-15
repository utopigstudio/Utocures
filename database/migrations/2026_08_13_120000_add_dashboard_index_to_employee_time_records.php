<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_time_records', function (Blueprint $table) {
            $table->index(
                ['date_in', 'date_out'],
                'employee_time_records_dashboard_date_range_idx',
            );
        });
    }

    public function down(): void
    {
        Schema::table('employee_time_records', function (Blueprint $table) {
            $table->dropIndex('employee_time_records_dashboard_date_range_idx');
        });
    }
};
