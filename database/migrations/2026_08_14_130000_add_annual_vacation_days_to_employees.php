<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DEFAULT_ANNUAL_VACATION_DAYS = 30;

    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->unsignedSmallInteger('annual_vacation_days')
                ->default(self::DEFAULT_ANNUAL_VACATION_DAYS)
                ->after('contract_end_date');
        });

        $this->backfillAnnualVacationDays();
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn('annual_vacation_days');
        });
    }

    private function backfillAnnualVacationDays(): void
    {
        $vacationDates = [];

        $periods = DB::table('employee_status_periods')
            ->select(['employee_id', 'start_at', 'end_at'])
            ->where('type', 'vacation')
            ->whereNull('deleted_at')
            ->orderBy('employee_id')
            ->orderBy('start_at')
            ->cursor();

        foreach ($periods as $period) {
            $date = Carbon::parse($period->start_at)->startOfDay();
            $endDate = Carbon::parse($period->end_at)->startOfDay();

            while ($date->lte($endDate)) {
                $vacationDates[$period->employee_id][$date->year][$date->toDateString()] = true;
                $date->addDay();
            }
        }

        foreach ($vacationDates as $employeeId => $datesByYear) {
            $maximumUsedDays = max(array_map('count', $datesByYear));

            if ($maximumUsedDays <= self::DEFAULT_ANNUAL_VACATION_DAYS) {
                continue;
            }

            DB::table('employees')
                ->where('id', $employeeId)
                ->update(['annual_vacation_days' => $maximumUsedDays]);
        }
    }
};
