<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\EmployeeStatusPeriod;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeStatusPeriodService
{
    public function create(Employee $employee, array $data, ?User $actor = null): EmployeeStatusPeriod
    {
        return DB::transaction(function () use ($employee, $data, $actor) {
            $this->ensureNoOverlaps(
                employee: $employee,
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
            $this->ensureNoOverlaps(
                employee: $period->employee,
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

    protected function normalizeDateTime(CarbonInterface|string $value): CarbonInterface
    {
        return $value instanceof CarbonInterface ? $value : Carbon::parse($value);
    }
}
