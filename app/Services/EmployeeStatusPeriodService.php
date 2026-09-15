<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeStatusPeriod;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeStatusPeriodService
{
    public function create(Employee $employee, array $data, ?User $actor = null): EmployeeStatusPeriod
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            Employee::query()->whereKey($employee->id)->lockForUpdate()->first();

            $this->ensureNoOverlaps(
                employee: $employee,
                startAt: $data['start_at'],
                endAt: $data['end_at'],
            );
            $this->ensureVacationAllowance(
                employee: $employee,
                type: $data['type'],
                startAt: $data['start_at'],
                endAt: $data['end_at'],
            );

            return $employee->statusPeriods()->create([
                ...$data,
                'created_by' => $actor?->id,
                'updated_by' => $actor?->id,
            ]);
        });
    }

    public function update(EmployeeStatusPeriod $period, array $data, ?User $actor = null): EmployeeStatusPeriod
    {
        return DB::transaction(function () use ($period, $data, $actor) {
            Employee::query()->whereKey($period->employee_id)->lockForUpdate()->first();

            $this->ensureNoOverlaps(
                employee: $period->employee,
                startAt: $data['start_at'],
                endAt: $data['end_at'],
                ignorePeriodId: $period->id,
            );
            $this->ensureVacationAllowance(
                employee: $period->employee,
                type: $data['type'],
                startAt: $data['start_at'],
                endAt: $data['end_at'],
                ignorePeriodId: $period->id,
            );

            $period->fill([
                ...$data,
                'updated_by' => $actor?->id,
            ]);
            $period->save();

            return $period->refresh();
        });
    }

    public function delete(EmployeeStatusPeriod $period, ?User $actor = null): void
    {
        DB::transaction(function () use ($period, $actor) {
            if ($actor?->id) {
                $period->forceFill(['updated_by' => $actor->id])->saveQuietly();
            }

            EmployeeStatusPeriod::query()
                ->whereKey($period->id)
                ->delete();
        });
    }

    /**
     * @param  Collection<int, EmployeeStatusPeriod>|null  $periods
     * @return array<int, array{year: int, allowance: int, used: int, remaining: int}>
     */
    public function vacationBalances(Employee $employee, ?Collection $periods = null): array
    {
        $vacationPeriods = ($periods ?? $employee->statusPeriods()->get())
            ->where('type', EmployeeStatusPeriod::TYPE_VACATION)
            ->values();

        $years = collect([now()->year]);

        foreach ($vacationPeriods as $period) {
            if (! $period->start_at || ! $period->end_at) {
                continue;
            }

            $years->push(...range($period->start_at->year, $period->end_at->year));
        }

        $allowance = (int) $employee->annual_vacation_days;

        return $years
            ->unique()
            ->sortDesc()
            ->values()
            ->map(function (int $year) use ($vacationPeriods, $allowance): array {
                $used = $this->vacationDatesForYear($vacationPeriods, $year)->count();

                return [
                    'year' => $year,
                    'allowance' => $allowance,
                    'used' => $used,
                    'remaining' => $allowance - $used,
                ];
            })
            ->all();
    }

    protected function ensureNoOverlaps(
        Employee $employee,
        CarbonInterface|string $startAt,
        CarbonInterface|string $endAt,
        ?string $ignorePeriodId = null,
    ): void {
        $startAt = $this->normalizeDateTime($startAt);
        $endAt = $this->normalizeDateTime($endAt);

        $hasOverlap = $employee->statusPeriods()
            ->when($ignorePeriodId, fn ($query) => $query->whereKeyNot($ignorePeriodId))
            ->where('start_at', '<', $endAt)
            ->where('end_at', '>', $startAt)
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_at' => __('employees.status_period_overlap'),
            ]);
        }
    }

    protected function ensureVacationAllowance(
        Employee $employee,
        string $type,
        CarbonInterface|string $startAt,
        CarbonInterface|string $endAt,
        ?string $ignorePeriodId = null,
    ): void {
        if ($type !== EmployeeStatusPeriod::TYPE_VACATION) {
            return;
        }

        $allowance = (int) $employee->annual_vacation_days;

        if ($allowance <= 0) {
            throw ValidationException::withMessages([
                'end_at' => __('employees.vacation_allowance_not_configured'),
            ]);
        }

        $existingPeriods = $employee->statusPeriods()
            ->where('type', EmployeeStatusPeriod::TYPE_VACATION)
            ->when($ignorePeriodId, fn ($query) => $query->whereKeyNot($ignorePeriodId))
            ->get();
        $startDate = $this->normalizeImmutableDateTime($startAt);
        $endDate = $this->normalizeImmutableDateTime($endAt);

        foreach (range($startDate->year, $endDate->year) as $year) {
            $usedDates = $this->vacationDatesForYear($existingPeriods, $year);
            $requestedDates = $this->vacationDateKeysInYear($startDate, $endDate, $year);
            $total = $usedDates->merge($requestedDates)->unique()->count();

            if ($total <= $allowance) {
                continue;
            }

            throw ValidationException::withMessages([
                'end_at' => __('employees.vacation_allowance_exceeded', [
                    'year' => $year,
                    'allowance' => $allowance,
                    'used' => $usedDates->count(),
                    'requested' => $requestedDates->count(),
                ]),
            ]);
        }
    }

    /**
     * @param  Collection<int, EmployeeStatusPeriod>  $periods
     * @return Collection<int, string>
     */
    protected function vacationDatesForYear(Collection $periods, int $year): Collection
    {
        return $periods
            ->flatMap(fn (EmployeeStatusPeriod $period) => $this->vacationDateKeysInYear(
                $period->start_at,
                $period->end_at,
                $year,
            ))
            ->unique()
            ->values();
    }

    /** @return Collection<int, string> */
    protected function vacationDateKeysInYear(
        CarbonInterface|string $startAt,
        CarbonInterface|string $endAt,
        int $year,
    ): Collection {
        $startDate = $this->normalizeImmutableDateTime($startAt)->startOfDay();
        $endDate = $this->normalizeImmutableDateTime($endAt)->startOfDay();
        $yearStart = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $yearEnd = CarbonImmutable::create($year, 12, 31)->startOfDay();

        if ($endDate->lt($yearStart) || $startDate->gt($yearEnd)) {
            return collect();
        }

        $currentDate = $startDate->greaterThan($yearStart) ? $startDate : $yearStart;
        $lastDate = $endDate->lessThan($yearEnd) ? $endDate : $yearEnd;
        $dates = collect();

        while ($currentDate->lte($lastDate)) {
            $dates->push($currentDate->toDateString());
            $currentDate = $currentDate->addDay();
        }

        return $dates;
    }

    protected function normalizeDateTime(CarbonInterface|string $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }

    protected function normalizeImmutableDateTime(CarbonInterface|string $value): CarbonImmutable
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);
    }
}
