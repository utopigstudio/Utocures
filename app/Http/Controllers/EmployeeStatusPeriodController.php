<?php

namespace App\Http\Controllers;

use App\Http\Requests\Employee\EmployeeStatusPeriodRequest;
use App\Models\Employee;
use App\Models\EmployeeStatusPeriod;
use App\Services\EmployeeStatusPeriodService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class EmployeeStatusPeriodController extends Controller
{
    public function store(
        EmployeeStatusPeriodRequest $request,
        Employee $employee,
        EmployeeStatusPeriodService $service,
    ): RedirectResponse {
        $service->create($employee, $request->validated(), $request->user());

        return back()->with('success', __('employees.status_period_created'));
    }

    public function update(
        EmployeeStatusPeriodRequest $request,
        Employee $employee,
        EmployeeStatusPeriod $statusPeriod,
        EmployeeStatusPeriodService $service,
    ): RedirectResponse {
        $this->ensureBelongsToEmployee($employee, $statusPeriod);

        $service->update($statusPeriod, $request->validated(), $request->user());

        return back()->with('success', __('employees.status_period_updated'));
    }

    public function destroy(
        Request $request,
        Employee $employee,
        EmployeeStatusPeriod $statusPeriod,
        EmployeeStatusPeriodService $service,
    ): RedirectResponse {
        $this->ensureBelongsToEmployee($employee, $statusPeriod);

        $service->delete($statusPeriod, $request->user());

        return back()->with('success', __('employees.status_period_deleted'));
    }

    protected function ensureBelongsToEmployee(Employee $employee, EmployeeStatusPeriod $statusPeriod): void
    {
        abort_if($statusPeriod->employee_id !== $employee->id, 404);
    }
}
