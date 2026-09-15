<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeTimeRecord;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isEmployee()) {
            $employee = $user->employee;

            abort_unless($employee, 403);

            $active_work = $employee->timeRecords()->whereNull('date_out')->with(['assignedHour', 'assignedHour.timeRecords', 'assignedHour.client', 'assignedHour.service'])->first();

            $works = $employee->assignedHours()
                ->with(['service', 'client', 'timeRecords'])
                ->where('date', now()->toDateString())
                ->orderBy('time_start', 'asc')
                ->get();

            $announcements = Announcement::query()
                ->select(['id', 'title', 'image', 'content', 'to', 'created_at'])
                ->visibleTo(Announcement::TO_EMPLOYEE)
                ->orderByDesc('created_at')
                ->get();

            return Inertia::render('EmployeeDashboard', [
                'works' => $works,
                'active_work' => $active_work,
                'announcements' => $announcements,
            ]);
        }

        if ($user->isClient()) {
            $client = $user->client;

            abort_unless($client, 403);

            return Inertia::render('ClientDashboard');
        }

        abort_unless($user->isAdmin(), 403);

        $hoursFilter = $this->resolveHoursFilter($request);

        return Inertia::render('Dashboard', [
            'clients_count' => fn () => Client::query()->count(),
            'employees_count' => fn () => Employee::query()
                ->whereHas('user', fn ($query) => $query->where('is_active', true))
                ->count(),
            'announcements' => fn () => Announcement::query()
                ->select(['id', 'title', 'image', 'content', 'to', 'created_at'])
                ->visibleTo(Announcement::TO_ADMIN)
                ->orderByDesc('created_at')
                ->get(),
            'hours_stats' => fn () => [
                'worked_minutes' => $this->workedMinutes(
                    $hoursFilter['from'],
                    $hoursFilter['to'],
                ),
            ],
            'hours_filter' => $hoursFilter,
        ]);
    }

    /**
     * @return array{
     *     period: string,
     *     year: int,
     *     month: int,
     *     from: string,
     *     to: string,
     *     min_year: int,
     *     max_year: int
     * }
     */
    private function resolveHoursFilter(Request $request): array
    {
        $filters = $request->validate([
            'hours_period' => ['nullable', Rule::in([
                'current_month',
                'previous_month',
                'current_quarter',
                'current_year',
                'previous_year',
                'month',
                'year',
                'custom',
            ])],
            'hours_year' => ['nullable', 'integer', 'between:2000,2100'],
            'hours_month' => ['nullable', 'integer', 'between:1,12'],
            'hours_from' => ['nullable', 'required_if:hours_period,custom', 'date_format:Y-m-d'],
            'hours_to' => ['nullable', 'required_if:hours_period,custom', 'date_format:Y-m-d', 'after_or_equal:hours_from'],
        ]);

        $today = CarbonImmutable::today();
        $period = $filters['hours_period'] ?? 'current_month';
        $year = (int) ($filters['hours_year'] ?? $today->year);
        $month = (int) ($filters['hours_month'] ?? $today->month);

        [$from, $to] = match ($period) {
            'previous_month' => [
                $today->subMonthNoOverflow()->startOfMonth(),
                $today->subMonthNoOverflow()->endOfMonth(),
            ],
            'current_quarter' => [$today->startOfQuarter(), $today->endOfQuarter()],
            'current_year' => [$today->startOfYear(), $today->endOfYear()],
            'previous_year' => [
                $today->subYear()->startOfYear(),
                $today->subYear()->endOfYear(),
            ],
            'month' => [
                CarbonImmutable::create($year, $month, 1)->startOfMonth(),
                CarbonImmutable::create($year, $month, 1)->endOfMonth(),
            ],
            'year' => [
                CarbonImmutable::create($year, 1, 1)->startOfYear(),
                CarbonImmutable::create($year, 1, 1)->endOfYear(),
            ],
            'custom' => [
                CarbonImmutable::createFromFormat('Y-m-d', $filters['hours_from'])->startOfDay(),
                CarbonImmutable::createFromFormat('Y-m-d', $filters['hours_to'])->endOfDay(),
            ],
            default => [$today->startOfMonth(), $today->endOfMonth()],
        };

        $firstRecordDate = EmployeeTimeRecord::query()->min('date_in');
        $firstRecordYear = $firstRecordDate
            ? CarbonImmutable::parse($firstRecordDate)->year
            : $today->year;

        return [
            'period' => $period,
            'year' => $year,
            'month' => $month,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'min_year' => min($firstRecordYear, $from->year, $today->year),
            'max_year' => max($to->year, $today->year),
        ];
    }

    private function workedMinutes(string $from, string $to): int
    {
        $rangeStart = CarbonImmutable::parse($from)->startOfDay()->toDateTimeString();
        $rangeEnd = CarbonImmutable::parse($to)->addDay()->startOfDay()->toDateTimeString();

        $query = EmployeeTimeRecord::query()
            ->where('date_in', '<=', $to)
            ->where('date_out', '>=', $from)
            ->whereNotNull('date_out')
            ->whereNotNull('time_out');

        [$durationExpression, $bindings] = match (DB::connection()->getDriverName()) {
            'sqlite' => [
                "MAX(0, (julianday(MIN(date_out || ' ' || time_out, ?)) - julianday(MAX(date_in || ' ' || time_in, ?))) * 86400)",
                [$rangeEnd, $rangeStart],
            ],
            'pgsql' => [
                'GREATEST(0, EXTRACT(EPOCH FROM (LEAST(date_out + time_out, CAST(? AS timestamp)) - GREATEST(date_in + time_in, CAST(? AS timestamp)))))',
                [$rangeEnd, $rangeStart],
            ],
            default => [
                'GREATEST(0, TIMESTAMPDIFF(SECOND, GREATEST(TIMESTAMP(date_in, time_in), CAST(? AS DATETIME)), LEAST(TIMESTAMP(date_out, time_out), CAST(? AS DATETIME))))',
                [$rangeStart, $rangeEnd],
            ],
        };

        $seconds = (int) $query
            ->selectRaw("COALESCE(SUM({$durationExpression}), 0) AS total_seconds", $bindings)
            ->value('total_seconds');

        return intdiv($seconds, 60);
    }
}
