<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->date('contract_end_date')->nullable()->after('hire_date');
            $table->index('contract_end_date', 'employees_contract_end_date_idx');
        });

        Schema::table('assigned_hours', function (Blueprint $table) {
            $table->index(['date', 'time_start'], 'assigned_hours_date_start_idx');
        });
    }

    public function down(): void
    {
        Schema::table('assigned_hours', function (Blueprint $table) {
            $table->dropIndex('assigned_hours_date_start_idx');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropIndex('employees_contract_end_date_idx');
            $table->dropColumn('contract_end_date');
        });
    }
};
