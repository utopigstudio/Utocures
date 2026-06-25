<?php

namespace App\Services;

use App\Models\AssignedHour;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignedHourService
{
    public function update(AssignedHour $assignedHour, array $data): AssignedHour
    {
        $this->ensureCanBeManaged($assignedHour);
        $this->ensureEmployeeCanPerformService($data['employee_id'], $assignedHour->service_id);
        $this->ensureNoOverlap(
            employeeId: $data['employee_id'],
            date: $data['date'],
            timeStart: $data['time_start'],
            timeEnd: $data['time_end'],
            ignoreAssignedHourId: $assignedHour->id,
        );

        return DB::transaction(function () use ($assignedHour, $data) {
            $assignedHour->update([
                'employee_id' => $data['employee_id'],
                'date' => $data['date'],
                'time_start' => $data['time_start'],
                'time_end' => $data['time_end'],
            ]);

            return $assignedHour->refresh();
        });
    }

    public function delete(AssignedHour $assignedHour): void
    {
        $this->ensureCanBeManaged($assignedHour);

        DB::transaction(function () use ($assignedHour) {
            AssignedHour::query()
                ->whereKey($assignedHour->id)
                ->delete();
        });
    }

    private function ensureCanBeManaged(AssignedHour $assignedHour): void
    {
        if ($assignedHour->timeRecords()->exists()) {
            throw ValidationException::withMessages([
                'date' => __('employees.assigned_hour_locked'),
            ]);
        }
    }

    private function ensureEmployeeCanPerformService(string $employeeId, string $serviceId): void
    {
        $canPerformService = Employee::query()
            ->whereKey($employeeId)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereHas('services', fn ($query) => $query->where('services.id', $serviceId))
            ->exists();

        if (! $canPerformService) {
            throw ValidationException::withMessages([
                'employee_id' => __('employees.assigned_hour_employee_service_mismatch'),
            ]);
        }
    }

    private function ensureNoOverlap(
        string $employeeId,
        string $date,
        string $timeStart,
        string $timeEnd,
        ?string $ignoreAssignedHourId = null,
    ): void {
        $hasOverlap = AssignedHour::query()
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->when($ignoreAssignedHourId, fn ($query) => $query->whereKeyNot($ignoreAssignedHourId))
            ->where('time_start', '<', $timeEnd)
            ->where('time_end', '>', $timeStart)
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'time_start' => __('employees.assigned_hour_overlap'),
            ]);
        }
    }
}
