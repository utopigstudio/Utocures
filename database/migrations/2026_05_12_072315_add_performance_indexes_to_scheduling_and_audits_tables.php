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
        Schema::table('assigned_hours', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'date', 'time_start', 'time_end'],
                'assigned_hours_employee_date_time_idx'
            );
        });

        Schema::table('available_hours', function (Blueprint $table) {
            $table->index(
                ['hourable_type', 'hourable_id', 'day_of_week', 'time_start', 'time_end'],
                'available_hours_hourable_day_time_idx'
            );
        });

        Schema::table('employee_time_records', function (Blueprint $table) {
            $table->index(
                ['employee_id', 'date_in', 'date_out'],
                'employee_time_records_employee_date_range_idx'
            );
        });

        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->index(
                ['auditable_type', 'auditable_id', 'event', 'created_at'],
                'audits_auditable_event_created_idx'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection(config('audit.drivers.database.connection', config('database.default')))
            ->table(config('audit.drivers.database.table', 'audits'), function (Blueprint $table) {
                $table->dropIndex('audits_auditable_event_created_idx');
            });

        Schema::table('employee_time_records', function (Blueprint $table) {
            $table->dropIndex('employee_time_records_employee_date_range_idx');
        });

        Schema::table('available_hours', function (Blueprint $table) {
            $table->dropIndex('available_hours_hourable_day_time_idx');
        });

        Schema::table('assigned_hours', function (Blueprint $table) {
            $table->dropIndex('assigned_hours_employee_date_time_idx');
        });
    }
};
