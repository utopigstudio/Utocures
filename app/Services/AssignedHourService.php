<?php

namespace App\Services;

use App\Models\AssignedHour;
use App\Models\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AssignedHourService
{
    public function __construct(private readonly AssignedHoursSchedulingService $schedulingService) {}

    public function update(AssignedHour $assignedHour, array $data): AssignedHour
    {
        $this->ensureCanBeManaged($assignedHour);
        $this->ensureEmployeeCanPerformService($data['employee_id'], $assignedHour->service_id);
        $employee = Employee::with('availableHours')->findOrFail($data['employee_id']);
        $scheduleData = [
            'recurrency' => 0,
            'days_of_week' => null,
            'date' => $data['date'],
            'date_start' => null,
            'date_end' => null,
            'time_start' => $data['time_start'],
            'time_end' => $data['time_end'],
        ];

        if (! $this->schedulingService->hasAvailability($employee, $scheduleData)) {
            throw ValidationException::withMessages([
                'employee_id' => __('clients.schedule_employee_outside_availability'),
            ]);
        }

        if ($this->schedulingService->hasConflict(
            employeeId: $data['employee_id'],
            data: $scheduleData,
            ignoreTemplateId: $assignedHour->assigned_hours_template_id,
            ignoreAssignedHourId: $assignedHour->id,
        )) {
            throw ValidationException::withMessages([
                'time_start' => __('employees.assigned_hour_overlap'),
            ]);
        }

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
}
