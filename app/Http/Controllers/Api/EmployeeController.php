<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Employee\EmployeeIndexRequest;
use App\Models\AssignedHoursTemplate;
use App\Models\Employee;
use App\Models\EmployeeStatusPeriod;
use App\Services\AssignedHoursSchedulingService;
use App\Services\RecurrenceCalculator;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly AssignedHoursSchedulingService $schedulingService,
        private readonly RecurrenceCalculator $recurrenceCalculator,
    ) {}

    public function index(EmployeeIndexRequest $request)
    {
        $data = $request->validated();

        $daysOfWeek = $data['days_of_week'] ?? [];
        $assignedHourId = $data['assigned_hour_id'] ?? null;
        $eventId = $data['event_id'] ?? null;
        $recurrency = $data['recurrency'] ?? null;
        $serviceId = $data['service_id'] ?? null;
        $timeStart = Carbon::parse($data['time_start'])->format('H:i');
        $timeEnd = Carbon::parse($data['time_end'])->format('H:i');
        $databaseTimeStart = Carbon::parse($data['time_start'])->format('H:i:s');
        $databaseTimeEnd = Carbon::parse($data['time_end'])->format('H:i:s');
        $date = isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : null;
        $dateStart = isset($data['date_start']) ? Carbon::parse($data['date_start'])->toDateString() : Carbon::now()->toDateString();
        $dateEnd = isset($data['date_end']) ? Carbon::parse($data['date_end'])->toDateString() : null;
        $occurrenceWindows = $this->buildOccurrenceTimeWindows(
            recurrency: $recurrency,
            daysOfWeek: $daysOfWeek,
            timeStart: $timeStart,
            timeEnd: $timeEnd,
            date: $date,
            dateStart: $dateStart,
            dateEnd: $dateEnd,
        );
        $statusCheckStart = $occurrenceWindows->min('start_at');
        $statusCheckEnd = $occurrenceWindows->max('end_at');
        $scheduleData = [
            'recurrency' => (int) $recurrency,
            'days_of_week' => $daysOfWeek,
            'time_start' => $timeStart,
            'time_end' => $timeEnd,
            'date' => $date,
            'date_start' => $dateStart,
            'recurrence_anchor_date' => $dateStart,
            'date_end' => $dateEnd,
        ];

        $employees = Employee::select(['id', 'user_id', 'phone'])
            ->with([
                'user:id,name,email,avatar',
                'statusPeriods' => fn ($query) => $query
                    ->where('start_at', '<', $statusCheckEnd)
                    ->where('end_at', '>', $statusCheckStart)
                    ->orderBy('start_at'),
            ])
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            });

        if ($recurrency === '0') {
            $employees->whereHas('availableHours', function ($q) use ($databaseTimeStart, $databaseTimeEnd, $date) {
                $dayOfWeek = Carbon::parse($date)->dayOfWeek;
                $q->where('day_of_week', $dayOfWeek)
                    ->where('time_start', '<=', $databaseTimeStart)
                    ->where('time_end', '>=', $databaseTimeEnd);
            });
        } else {
            foreach ($daysOfWeek as $day) {
                $employees->whereHas('availableHours', function ($q) use ($day, $databaseTimeStart, $databaseTimeEnd) {
                    $q->where('day_of_week', $day)
                        ->where('time_start', '<=', $databaseTimeStart)
                        ->where('time_end', '>=', $databaseTimeEnd);
                });
            }
        }

        $employees = $employees->get()
            ->reject(fn (Employee $employee) => $this->schedulingService->hasConflict(
                employeeId: $employee->id,
                data: $scheduleData,
                ignoreTemplateId: $eventId,
                ignoreAssignedHourId: $assignedHourId,
            ))
            ->values()
            ->map(function (Employee $employee) use ($occurrenceWindows) {
                /** @var EmployeeStatusPeriod|null $blockingPeriod */
                $blockingPeriod = $this->findFirstBlockingStatusPeriod($employee->statusPeriods, $occurrenceWindows);

                return [
                    'id' => $employee->id,
                    'phone' => $employee->phone,
                    'user' => $employee->user,
                    'is_selectable' => true,
                    'unavailability_reason' => $blockingPeriod
                        ? $this->formatUnavailabilityReason($blockingPeriod)
                        : null,
                    'blocking_status_period' => $blockingPeriod,
                ];
            })
            ->values();

        return response()->json(['data' => $employees]);
    }

    private function buildOccurrenceTimeWindows(
        string $recurrency,
        array $daysOfWeek,
        string $timeStart,
        string $timeEnd,
        ?string $date,
        string $dateStart,
        ?string $dateEnd,
    ): Collection {
        if ($recurrency === '0' && $date) {
            return collect([[
                'start_at' => Carbon::parse("{$date} {$timeStart}"),
                'end_at' => Carbon::parse("{$date} {$timeEnd}"),
            ]]);
        }

        $baseStart = Carbon::parse($dateStart);
        $baseEnd = $dateEnd
            ? Carbon::parse($dateEnd)
            : $baseStart->copy()->addDays(AssignedHoursTemplate::DAYS_GENERATE);
        $selectedDays = collect($daysOfWeek)
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->values();

        if ($selectedDays->isEmpty()) {
            return collect([[
                'start_at' => Carbon::parse($baseStart->format('Y-m-d')." {$timeStart}"),
                'end_at' => Carbon::parse($baseStart->format('Y-m-d')." {$timeEnd}"),
            ]]);
        }

        $occurrences = $this->buildRecurrentOccurrenceTimeWindows(
            daysOfWeek: $daysOfWeek,
            timeStart: $timeStart,
            timeEnd: $timeEnd,
            rangeStart: $baseStart->toDateString(),
            rangeEnd: $baseEnd->toDateString(),
            anchorDateStart: $baseStart->toDateString(),
            recurrency: $recurrency,
        );

        if ($occurrences->isNotEmpty()) {
            return $occurrences;
        }

        return collect([[
            'start_at' => Carbon::parse($baseStart->format('Y-m-d')." {$timeStart}"),
            'end_at' => Carbon::parse($baseStart->format('Y-m-d')." {$timeEnd}"),
        ]]);
    }

    private function buildRecurrentOccurrenceTimeWindows(
        array $daysOfWeek,
        string $timeStart,
        string $timeEnd,
        string $rangeStart,
        string $rangeEnd,
        string $anchorDateStart,
        string $recurrency,
    ): Collection {
        return $this->recurrenceCalculator
            ->dates(
                recurrency: (int) $recurrency,
                daysOfWeek: $daysOfWeek,
                rangeStart: $rangeStart,
                rangeEnd: $rangeEnd,
                anchorDate: $anchorDateStart,
            )
            ->map(fn ($candidate) => [
                'start_at' => Carbon::parse($candidate->format('Y-m-d')." {$timeStart}"),
                'end_at' => Carbon::parse($candidate->format('Y-m-d')." {$timeEnd}"),
            ]);
    }

    private function findFirstBlockingStatusPeriod(Collection $periods, Collection $occurrenceWindows): ?EmployeeStatusPeriod
    {
        foreach ($occurrenceWindows as $window) {
            /** @var EmployeeStatusPeriod|null $blockingPeriod */
            $blockingPeriod = $periods->first(function (EmployeeStatusPeriod $period) use ($window) {
                return $window['start_at']->lt($period->end_at) && $window['end_at']->gt($period->start_at);
            });

            if ($blockingPeriod) {
                return $blockingPeriod;
            }
        }

        return null;
    }

    private function formatUnavailabilityReason(EmployeeStatusPeriod $period): string
    {
        $startDate = $period->start_at->format('d/m/Y');
        $endDate = $period->end_at->format('d/m/Y');

        if ($period->start_at->isSameDay($period->end_at)) {
            return __('clients.employee_absent_on', [
                'date' => $startDate,
            ]);
        }

        return __('clients.employee_absent_between', [
            'start' => $startDate,
            'end' => $endDate,
        ]);
    }
}
