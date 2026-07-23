<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Employee\EmployeeIndexRequest;
use App\Models\AssignedHoursTemplate;
use App\Models\Employee;
use App\Models\EmployeeStatusPeriod;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class EmployeeController extends Controller
{
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
        $date = isset($data['date']) ? Carbon::parse($data['date'])->toDateString() : null;
        $dateStart = isset($data['date_start']) ? Carbon::parse($data['date_start'])->toDateString() : Carbon::now()->toDateString();
        $dateEnd = isset($data['date_end']) ? Carbon::parse($data['date_end'])->toDateString() : null;
        $occurrenceCheckEnd = $dateEnd
            ?: Carbon::parse($dateStart)->addDays(AssignedHoursTemplate::DAYS_GENERATE)->toDateString();
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

        $employees = Employee::select(['id', 'user_id', 'phone'])
            ->with([
                'user:id,name,email,avatar',
                'statusPeriods' => fn ($query) => $query
                    ->where('start_at', '<', $statusCheckEnd)
                    ->where('end_at', '>', $statusCheckStart)
                    ->orderBy('start_at'),
            ])
            ->when($recurrency !== '0', fn ($query) => $query->with([
                'assignedHoursTemplates' => fn ($assignedHoursTemplatesQuery) => $this->scopeRecurringTemplateCandidates(
                    query: $assignedHoursTemplatesQuery,
                    eventId: $eventId,
                    timeStart: $timeStart,
                    timeEnd: $timeEnd,
                    dateStart: $dateStart,
                    dateEnd: $occurrenceCheckEnd,
                ),
            ]))
            ->whereHas('user', function ($q) {
                $q->where('is_active', true);
            })
            ->whereHas('services', function ($q) use ($serviceId) {
                $q->where('services.id', $serviceId);
            })
            ->when($recurrency === '0' && $date, function ($query) use ($assignedHourId, $date, $timeStart, $timeEnd) {
                $query->whereDoesntHave('assignedHours', function ($assignedHoursQuery) use ($assignedHourId, $date, $timeStart, $timeEnd) {
                    $assignedHoursQuery
                        ->when($assignedHourId, fn ($innerQuery) => $innerQuery->whereKeyNot($assignedHourId))
                        ->where('date', $date)
                        ->where('time_start', '<', $timeEnd)
                        ->where('time_end', '>', $timeStart);
                });
            })
            ->when($recurrency === '0', fn ($query) => $query->whereDoesntHave('assignedHoursTemplates', function ($q) use (
                $eventId,
                $timeStart,
                $timeEnd,
                $date,
            ) {
                $q->when($eventId, fn ($qq) => $qq->where('id', '!=', $eventId))
                    ->where('time_start', '<', $timeEnd)
                    ->where('time_end', '>', $timeStart)
                    ->where('recurrency', 0)
                    ->where('date', $date);
            }));

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

        $employees = $employees->get()
            ->when($recurrency !== '0', fn (Collection $employees) => $employees
                ->reject(fn (Employee $employee) => $this->hasConflictingRecurringTemplate(
                    templates: $employee->assignedHoursTemplates,
                    requestedWindows: $occurrenceWindows,
                    dateStart: $dateStart,
                    dateEnd: $occurrenceCheckEnd,
                ))
                ->values())
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

    private function scopeRecurringTemplateCandidates(
        $query,
        ?string $eventId,
        string $timeStart,
        string $timeEnd,
        string $dateStart,
        string $dateEnd,
    ) {
        return $query
            ->when($eventId, fn ($qq) => $qq->where('id', '!=', $eventId))
            ->where('time_start', '<', $timeEnd)
            ->where('time_end', '>', $timeStart)
            ->where('recurrency', '!=', 0)
            ->where(function ($qq) use ($dateStart, $dateEnd) {
                $qq->where(function ($q2) use ($dateStart) {
                    $q2->whereNull('date_end')
                        ->orWhere('date_end', '>=', $dateStart);
                })
                    ->where(function ($q2) use ($dateEnd) {
                        $q2->whereNull('date_start')
                            ->orWhere('date_start', '<=', $dateEnd);
                    });
            });
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
        $start = Carbon::parse($rangeStart);
        $end = Carbon::parse($rangeEnd);

        if ($start->greaterThan($end)) {
            return collect();
        }

        $selectedDays = collect($daysOfWeek)
            ->map(fn ($day) => (int) $day)
            ->unique()
            ->values();

        if ($selectedDays->isEmpty()) {
            return collect();
        }

        $anchorWeekStart = Carbon::parse($anchorDateStart)->startOfWeek();
        $candidate = $start->copy();
        $occurrences = collect();

        while ($candidate->lessThanOrEqualTo($end)) {
            if ($selectedDays->contains($candidate->dayOfWeek) && $this->matchesRecurrencePattern($candidate, $anchorWeekStart, $recurrency)) {
                $occurrences->push([
                    'start_at' => Carbon::parse($candidate->format('Y-m-d')." {$timeStart}"),
                    'end_at' => Carbon::parse($candidate->format('Y-m-d')." {$timeEnd}"),
                ]);
            }

            $candidate->addDay();
        }

        return $occurrences;
    }

    private function matchesRecurrencePattern(
        CarbonInterface $candidate,
        CarbonInterface $anchorWeekStart,
        string $recurrency,
    ): bool {
        if ($recurrency === '1') {
            return true;
        }

        if ($recurrency !== '2') {
            return false;
        }

        $weekDiff = $anchorWeekStart->diffInWeeks($candidate->copy()->startOfWeek());

        return $weekDiff % 2 === 0;
    }

    private function hasConflictingRecurringTemplate(
        Collection $templates,
        Collection $requestedWindows,
        string $dateStart,
        string $dateEnd,
    ): bool {
        return $templates->contains(function (AssignedHoursTemplate $template) use ($requestedWindows, $dateStart, $dateEnd) {
            $templateStart = $template->date_start?->toDateString() ?? Carbon::today()->toDateString();
            $templateEnd = $template->date_end?->toDateString() ?? $dateEnd;
            $overlapStart = Carbon::parse($templateStart)->greaterThan(Carbon::parse($dateStart))
                ? $templateStart
                : $dateStart;
            $overlapEnd = Carbon::parse($templateEnd)->lessThan(Carbon::parse($dateEnd))
                ? $templateEnd
                : $dateEnd;

            $templateWindows = $this->buildRecurrentOccurrenceTimeWindows(
                daysOfWeek: $template->days_of_week ?? [],
                timeStart: $template->time_start->format('H:i'),
                timeEnd: $template->time_end->format('H:i'),
                rangeStart: $overlapStart,
                rangeEnd: $overlapEnd,
                anchorDateStart: $templateStart,
                recurrency: (string) $template->recurrency,
            );

            return $this->timeWindowsOverlap($requestedWindows, $templateWindows);
        });
    }

    private function timeWindowsOverlap(Collection $leftWindows, Collection $rightWindows): bool
    {
        foreach ($leftWindows as $leftWindow) {
            foreach ($rightWindows as $rightWindow) {
                if ($leftWindow['start_at']->lt($rightWindow['end_at']) && $leftWindow['end_at']->gt($rightWindow['start_at'])) {
                    return true;
                }
            }
        }

        return false;
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
