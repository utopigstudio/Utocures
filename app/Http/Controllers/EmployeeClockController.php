<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeClockRequest;
use App\Models\Employee;
use App\Models\EmployeeTimeRecordLocation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeClockController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->time_record_mode === Employee::TIME_RECORD_FREE, 403);

        return response()->json([
            'time_record' => $employee->timeRecords()->whereNull('assigned_hour_id')->whereNull('time_out')->first(),
        ]);
    }

    public function store(EmployeeClockRequest $request): RedirectResponse
    {
        $data = $request->validated();
        DB::transaction(function () use ($request, $data): void {
            $employee = Employee::whereKey($request->user()->employee->id)->lockForUpdate()->firstOrFail();
            abort_unless($employee->time_record_mode === Employee::TIME_RECORD_FREE, 403);
            $active = $employee->timeRecords()->whereNull('time_out')->first();
            $now = now();
            if ($data['action'] === 'in') {
                if ($active) {
                    throw ValidationException::withMessages(['time_record' => __('employee_time_records.active_record_warning')]);
                }
                $record = $employee->timeRecords()->create([
                    'date_in' => $now->toDateString(), 'time_in' => $now->format('H:i:s'),
                ]);
            } else {
                if (! $active || $active->assigned_hour_id || $active->id !== $data['time_record_id']) {
                    throw ValidationException::withMessages(['time_record' => __('employee_time_records.clock_state_changed')]);
                }
                $record = $active;
                $record->update(['date_out' => $now->toDateString(), 'time_out' => $now->format('H:i:s')]);
            }
            if (! empty($data['location'])) {
                $record->locations()->create([
                    'event_type' => $data['action'] === 'in' ? EmployeeTimeRecordLocation::EVENT_IN : EmployeeTimeRecordLocation::EVENT_OUT,
                    ...$data['location'],
                ]);
            }
        });

        return back()->with('success', __('employee_time_records.clock_saved'));
    }
}
