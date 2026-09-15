<?php

namespace App\Services;

use App\Models\AssignedHour;
use App\Models\AssignedHoursTemplate;
use App\Models\Client;
use App\Models\Employee;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

class AssignedHoursSchedulingService
{
    public function __construct(private readonly RecurrenceCalculator $recurrenceCalculator) {}

    public function normalizeTemplateData(array $data, ?AssignedHoursTemplate $existing = null): array
    {
        $recurrency = (int) $data['recurrency'];
        $data['recurrency'] = $recurrency;

        if ($recurrency === 0) {
            $data['days_of_week'] = null;
            $data['date_start'] = null;
            $data['recurrence_anchor_date'] = null;
            $data['date_end'] = null;

            return $data;
        }

        $dateStart = $data['date_start'] ?? null;
        $existingDateStart = $existing?->date_start?->toDateString();
        $preserveAnchor = $existing
            && (int) $existing->recurrency === $recurrency
            && (! $dateStart || $dateStart === $existingDateStart);

        $data['date'] = null;
        $normalizedDays = collect($data['days_of_week'] ?? [])
            ->map(fn ($day) => (string) (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $existingDays = $existing
            ? collect($existing->days_of_week ?? [])->map(fn ($day) => (string) (int) $day)->sort()->values()->all()
            : null;
        $data['days_of_week'] = $existing && $existingDays === $normalizedDays
            ? $existing->days_of_week
            : $normalizedDays;
        $data['date_start'] = $dateStart ?: today()->toDateString();
        $data['recurrence_anchor_date'] = $preserveAnchor
            ? $existing->recurrenceAnchorDate()->toDateString()
            : $data['date_start'];

        return $data;
    }

    public function ensureTemplateCanBeAssigned(
        Client $client,
        array $data,
        ?AssignedHoursTemplate $ignoreTemplate = null,
    ): void {
        $serviceId = (string) $data['service_id'];
        $employeeId = (string) $data['employee_id'];

        if (! $client->services()->whereKey($serviceId)->exists()) {
            throw ValidationException::withMessages([
                'service_id' => __('clients.schedule_service_not_assigned'),
            ]);
        }

        $employee = Employee::query()
            ->with('availableHours')
            ->whereKey($employeeId)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->whereHas('services', fn ($query) => $query->where('services.id', $serviceId))
            ->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee_id' => __('employees.assigned_hour_employee_service_mismatch'),
            ]);
        }

        if (! $this->hasAvailability($employee, $data)) {
            throw ValidationException::withMessages([
                'employee_id' => __('clients.schedule_employee_outside_availability'),
            ]);
        }

        if ($this->hasConflict(
            employeeId: $employeeId,
            data: $data,
            ignoreTemplateId: $ignoreTemplate?->id,
        )) {
            throw ValidationException::withMessages([
                'time_start' => __('employees.assigned_hour_overlap'),
            ]);
        }
    }

    public function hasAvailability(Employee $employee, array $data): bool
    {
        $recurrency = (int) $data['recurrency'];
        $days = $recurrency === 0
            ? [CarbonImmutable::parse($data['date'])->dayOfWeek]
            : array_map('intval', $data['days_of_week'] ?? []);

        foreach (array_unique($days) as $day) {
            $isAvailable = $employee->availableHours->contains(function ($availableHour) use ($day, $data) {
                return (int) $availableHour->day_of_week === $day
                    && $availableHour->time_start->format('H:i') <= $data['time_start']
                    && $availableHour->time_end->format('H:i') >= $data['time_end'];
            });

            if (! $isAvailable) {
                return false;
            }
        }

        return true;
    }

    public function hasConflict(
        string $employeeId,
        array $data,
        ?string $ignoreTemplateId = null,
        ?string $ignoreAssignedHourId = null,
    ): bool {
        $templates = AssignedHoursTemplate::query()
            ->where('employee_id', $employeeId)
            ->when($ignoreTemplateId, fn ($query) => $query->whereKeyNot($ignoreTemplateId))
            ->where('time_start', '<', $this->databaseTime($data['time_end']))
            ->where('time_end', '>', $this->databaseTime($data['time_start']))
            ->get();

        if ($templates->contains(fn (AssignedHoursTemplate $template) => $this->templatesOverlap($data, $template))) {
            return true;
        }

        $assignedHours = AssignedHour::query()
            ->where('employee_id', $employeeId)
            ->when($ignoreAssignedHourId, fn ($query) => $query->whereKeyNot($ignoreAssignedHourId))
            ->when(
                $ignoreTemplateId && ! $ignoreAssignedHourId,
                fn ($query) => $query->where('assigned_hours_template_id', '!=', $ignoreTemplateId),
            )
            ->where('time_start', '<', $this->databaseTime($data['time_end']))
            ->where('time_end', '>', $this->databaseTime($data['time_start']));

        if ((int) $data['recurrency'] === 0) {
            return $assignedHours->where('date', $data['date'])->exists();
        }

        $rangeStart = $this->effectiveStart($data);
        $assignedHours->where('date', '>=', $rangeStart->toDateString());
        if (! empty($data['date_end'])) {
            $assignedHours->where('date', '<=', $data['date_end']);
        }

        return $assignedHours->pluck('date')->contains(
            fn ($date) => $this->requestedScheduleOccursOn($data, CarbonImmutable::parse($date)),
        );
    }

    private function templatesOverlap(array $requested, AssignedHoursTemplate $existing): bool
    {
        $requestedRecurrency = (int) $requested['recurrency'];
        $existingRecurrency = (int) $existing->recurrency;

        if ($requestedRecurrency === 0) {
            $date = CarbonImmutable::parse($requested['date']);

            return $existingRecurrency === 0
                ? $existing->date?->isSameDay($date) === true
                : $this->templateOccursOn($existing, $date);
        }

        if ($existingRecurrency === 0) {
            return $existing->date
                ? $this->requestedScheduleOccursOn($requested, CarbonImmutable::instance($existing->date))
                : false;
        }

        $requestedStart = $this->effectiveStart($requested);
        $existingStart = $existing->date_start
            ? CarbonImmutable::instance($existing->date_start)
            : $existing->recurrenceAnchorDate();
        $overlapStart = $requestedStart->greaterThan($existingStart) ? $requestedStart : $existingStart;
        $requestedEnd = ! empty($requested['date_end']) ? CarbonImmutable::parse($requested['date_end']) : null;
        $existingEnd = $existing->date_end ? CarbonImmutable::instance($existing->date_end) : null;
        $overlapEnd = $overlapStart->addDays(27);

        if ($requestedEnd && $requestedEnd->lessThan($overlapEnd)) {
            $overlapEnd = $requestedEnd;
        }
        if ($existingEnd && $existingEnd->lessThan($overlapEnd)) {
            $overlapEnd = $existingEnd;
        }
        if ($overlapEnd->lessThan($overlapStart)) {
            return false;
        }

        $requestedDates = $this->recurrenceCalculator->dates(
            recurrency: $requestedRecurrency,
            daysOfWeek: $requested['days_of_week'] ?? [],
            rangeStart: $overlapStart,
            rangeEnd: $overlapEnd,
            anchorDate: $this->requestedAnchor($requested),
        )->map->toDateString();
        $existingDates = $this->recurrenceCalculator->dates(
            recurrency: $existingRecurrency,
            daysOfWeek: $existing->days_of_week ?? [],
            rangeStart: $overlapStart,
            rangeEnd: $overlapEnd,
            anchorDate: $existing->recurrenceAnchorDate(),
        )->map->toDateString();

        return $requestedDates->intersect($existingDates)->isNotEmpty();
    }

    private function requestedScheduleOccursOn(array $data, CarbonImmutable $date): bool
    {
        if ((int) $data['recurrency'] === 0) {
            return $date->isSameDay(CarbonImmutable::parse($data['date']));
        }

        return $this->recurrenceCalculator->occursOn(
            recurrency: (int) $data['recurrency'],
            daysOfWeek: $data['days_of_week'] ?? [],
            date: $date,
            anchorDate: $this->requestedAnchor($data),
            dateEnd: $data['date_end'] ?? null,
        ) && $date->greaterThanOrEqualTo($this->effectiveStart($data));
    }

    private function templateOccursOn(AssignedHoursTemplate $template, CarbonImmutable $date): bool
    {
        $effectiveStart = $template->date_start
            ? CarbonImmutable::instance($template->date_start)
            : $template->recurrenceAnchorDate();

        return $date->greaterThanOrEqualTo($effectiveStart)
            && $this->recurrenceCalculator->occursOn(
                recurrency: (int) $template->recurrency,
                daysOfWeek: $template->days_of_week ?? [],
                date: $date,
                anchorDate: $template->recurrenceAnchorDate(),
                dateEnd: $template->date_end,
            );
    }

    private function effectiveStart(array $data): CarbonImmutable
    {
        $start = CarbonImmutable::parse($data['date_start'] ?? $this->requestedAnchor($data));
        $today = CarbonImmutable::today();

        return $start->greaterThan($today) ? $start : $today;
    }

    private function requestedAnchor(array $data): CarbonImmutable
    {
        return CarbonImmutable::parse(
            $data['recurrence_anchor_date']
                ?? $data['date_start']
                ?? today(),
        );
    }

    private function databaseTime(string $time): string
    {
        return Carbon::parse($time)->format('H:i:s');
    }
}
