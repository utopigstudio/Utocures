<?php

namespace App\Observers;

use App\Models\AssignedHoursTemplate;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class AssignedHoursTemplateObserver
{
    private static array $originalAttributes = [];

    public function updating(AssignedHoursTemplate $assignedHoursTemplate): void
    {
        self::$originalAttributes[$assignedHoursTemplate->id] = $assignedHoursTemplate->scheduleOriginalAttributes
            ?: $assignedHoursTemplate->getRawOriginal();
    }

    public function created(AssignedHoursTemplate $assignedHoursTemplate): void
    {
        $assignedHoursTemplate->generateAssignedHours();
    }

    public function updated(AssignedHoursTemplate $assignedHoursTemplate): void
    {
        $assignedHoursTemplate->scheduleOriginalAttributes = self::$originalAttributes[$assignedHoursTemplate->id] ?? [];
        unset(self::$originalAttributes[$assignedHoursTemplate->id]);

        $fieldsChanged = Arr::except($assignedHoursTemplate->getChanges(), [
            'updated_at',
            'generated_until',
        ]);

        if ($fieldsChanged === []) {
            return;
        }

        $originalDateStart = $this->originalDateStart($assignedHoursTemplate);

        if (! $originalDateStart || $originalDateStart->greaterThanOrEqualTo(CarbonImmutable::today())) {
            $this->regenerateAll($assignedHoursTemplate);

            return;
        }

        if (array_keys($fieldsChanged) === ['date_end']) {
            $this->applyEndDateChange($assignedHoursTemplate);

            return;
        }

        $this->splitHistoricalTemplate($assignedHoursTemplate);
    }

    private function regenerateAll(AssignedHoursTemplate $template): void
    {
        $hours = $template->assignedHours();
        $this->ensureHoursAreUnlocked(clone $hours);
        $hours->delete();

        $template->forceFill(['generated_until' => null])->saveQuietly();
        $template->generateAssignedHours();
    }

    private function applyEndDateChange(AssignedHoursTemplate $template): void
    {
        $newDateEnd = $template->date_end
            ? CarbonImmutable::instance($template->date_end)
            : null;
        $originalDateEndValue = $this->previousValue($template, 'date_end');
        $originalDateEnd = $originalDateEndValue
            ? CarbonImmutable::parse($originalDateEndValue)
            : null;

        if ($newDateEnd && (! $originalDateEnd || $newDateEnd->lessThan($originalDateEnd))) {
            $hoursToRemove = $template->assignedHours()->where('date', '>', $newDateEnd->toDateString());
            $this->ensureHoursAreUnlocked(clone $hoursToRemove);
            $hoursToRemove->delete();

            $template->forceFill(['generated_until' => $newDateEnd->toDateString()])->saveQuietly();
        }

        $template->generateAssignedHours();
    }

    private function splitHistoricalTemplate(AssignedHoursTemplate $template): void
    {
        $today = CarbonImmutable::today();
        $yesterday = $today->subDay();
        $futureHours = $template->assignedHours()->where('date', '>', $yesterday->toDateString());
        $this->ensureHoursAreUnlocked(clone $futureHours);

        $newTemplate = $template->replicate(['id', 'created_at', 'updated_at']);
        $newTemplate->generated_until = null;

        if ((int) $newTemplate->recurrency > 0) {
            $requestedStart = $newTemplate->date_start
                ? CarbonImmutable::instance($newTemplate->date_start)
                : $today;
            $newTemplate->date_start = $requestedStart->greaterThan($today)
                ? $requestedStart
                : $today;
        }

        $historicalAttributes = array_replace(
            $template->getAttributes(),
            $template->getPrevious(),
            $template->scheduleOriginalAttributes,
        );
        $originalAttributes = Arr::only($historicalAttributes, [
            'days_of_week',
            'date',
            'recurrency',
            'time_start',
            'time_end',
            'date_start',
            'recurrence_anchor_date',
            'date_end',
            'employee_id',
            'client_id',
            'service_id',
            'generated_until',
        ]);
        $originalAttributes['date_end'] = $yesterday->toDateString();
        $originalAttributes['generated_until'] = $yesterday->toDateString();

        $futureHours->delete();
        $template->syncOriginal();
        $template->forceFill($originalAttributes)->saveQuietly();
        $newTemplate->save();
    }

    private function originalDateStart(AssignedHoursTemplate $template): ?CarbonImmutable
    {
        $originalDateStart = $this->previousValue($template, 'date_start');
        if ($originalDateStart) {
            return CarbonImmutable::parse($originalDateStart);
        }

        $firstAssignedDate = $template->assignedHours()->min('date');

        return $firstAssignedDate ? CarbonImmutable::parse($firstAssignedDate) : null;
    }

    private function previousValue(AssignedHoursTemplate $template, string $key): mixed
    {
        if (array_key_exists($key, $template->scheduleOriginalAttributes)) {
            return $template->scheduleOriginalAttributes[$key];
        }

        $previous = $template->getPrevious();

        return array_key_exists($key, $previous)
            ? $previous[$key]
            : $template->getRawOriginal($key);
    }

    private function ensureHoursAreUnlocked(Builder|Relation $hours): void
    {
        if ($hours->whereHas('timeRecords')->exists()) {
            throw ValidationException::withMessages([
                'date_start' => __('employees.assigned_hour_locked'),
            ]);
        }
    }
}
