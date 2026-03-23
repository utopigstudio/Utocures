<?php

namespace App\Http\Controllers\Api;

use App\Models\Employee;
use App\Http\Controllers\Controller;
use Carbon\Carbon;
use App\Http\Requests\Api\Employee\EmployeeIndexRequest;

class EmployeeController extends Controller
{
    public function index(EmployeeIndexRequest $request)
    {
        $data = $request->validated();

        $daysOfWeek = $data['days_of_week'] ?? [];
        $eventId = $data['event_id'] ?? null;
        $recurrency = $data['recurrency'] ?? null;
        $serviceId = $data['service_id'] ?? null;
        $timeStart = Carbon::parse($data['time_start'])->format('H:i');
        $timeEnd = Carbon::parse($data['time_end'])->format('H:i');
        $date = isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : null;
        $dateStart = isset($data['date_start']) ? Carbon::parse($data['date_start'])->toDateString() : Carbon::now()->toDateString();
        $dateEnd = isset($data['date_end']) ? Carbon::parse($data['date_end'])->toDateString() : null;

        $employees = Employee::select(['id', 'user_id'])
            ->with(['user:id,name,email,avatar'])
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            })
            ->whereDoesntHave('assignedHoursTemplates', function ($q) use (
                $eventId,
                $recurrency,
                $daysOfWeek,
                $timeStart,
                $timeEnd,
                $date,
                $dateStart,
                $dateEnd,
            ) {
                $q->when($eventId, fn ($qq) => $qq->where('id', '!=', $eventId))
                    ->where('time_start', '<', $timeEnd)
                    ->where('time_end', '>', $timeStart);

                // EVENTO RECURRENTE (recurrency == 0)
                if ($recurrency === '0') {
                    $q->where('recurrency', 0)
                        ->whereDate('date', $date);
                }

                // EVENTO RECURRENTE (recurrency != 0)
                if ($recurrency !== '0') {
                    $q->where('recurrency', '!=', 0)
                    // Coincidencia de días de la semana
                    ->where(function ($qq) use ($daysOfWeek) {
                        foreach ($daysOfWeek as $day) {
                            $qq->orWhereJsonContains('days_of_week', $day);
                        }
                    })
                    // Cruce de rangos de fechas (si existen)
                    ->where(function ($qq) use ($dateStart, $dateEnd) {
                        $qq->where(function ($q2) use ($dateStart) {
                            $q2->whereNull('date_end')
                                ->orWhere('date_end', '>=', $dateStart);
                        });

                        if ($dateEnd) {
                            $qq->where(function ($q2) use ($dateEnd) {
                                $q2->whereNull('date_start')
                                    ->orWhere('date_start', '<=', $dateEnd);
                            });
                        }
                    });
                }
            });

            if ($recurrency === '0') {
                $employees->whereHas('availableHours', function ($q) use ($timeStart, $timeEnd, $date) {
                    $dayOfWeek = Carbon::parse($date)->dayOfWeek;
                    $q->where('day_of_week', $dayOfWeek)
                    ->where('time_start', '<=', $timeStart)
                    ->where('time_end', '>=', $timeEnd);
                });
            } else {
                foreach ($daysOfWeek as $day) {
                    $employees->whereHas('availableHours', function ($q) use ($day, $timeStart, $timeEnd) {
                        $q->where('day_of_week', $day)
                        ->where('time_start', '<=', $timeStart)
                        ->where('time_end', '>=', $timeEnd);
                    });
                }
            }

            $employees = $employees->get();

        return response()->json(['data' => $employees]);
    }
}
