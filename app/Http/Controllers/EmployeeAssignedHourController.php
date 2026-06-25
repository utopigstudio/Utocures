<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssignedHour\AssignedHourUpdateRequest;
use App\Models\AssignedHour;
use App\Models\Employee;
use App\Services\AssignedHourService;
use Illuminate\Http\RedirectResponse;

class EmployeeAssignedHourController extends Controller
{
    public function update(
        AssignedHourUpdateRequest $request,
        Employee $employee,
        AssignedHour $assignedHour,
        AssignedHourService $service,
    ): RedirectResponse {
        $this->ensureBelongsToEmployee($employee, $assignedHour);

        $service->update($assignedHour, $request->validated());

        return back()->with('success', __('employees.assigned_hour_updated'));
    }

    public function destroy(
        Employee $employee,
        AssignedHour $assignedHour,
        AssignedHourService $service,
    ): RedirectResponse {
        $this->ensureBelongsToEmployee($employee, $assignedHour);

        $service->delete($assignedHour);

        return back()->with('success', __('employees.assigned_hour_deleted'));
    }

    private function ensureBelongsToEmployee(Employee $employee, AssignedHour $assignedHour): void
    {
        abort_if($assignedHour->employee_id !== $employee->id, 404);
    }
}
