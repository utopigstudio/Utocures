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

        if ((int) $assignedHoursTemplate->recurrency > 0 && array_keys($fieldsChanged) === ['date_end']) {
            $this->applyEndDateChange($assignedHoursTemplate);

            return;
        }

        $today = CarbonImmutable::today();
        $requestedStart = $assignedHoursTemplate->date_start ?? $assignedHoursTemplate->date;
        $changeStart = $requestedStart && $requestedStart->greaterThan($today)
            ? CarbonImmutable::instance($requestedStart)
            : $today;
        $originalDateStart = $this->originalDateStart($assignedHoursTemplate);

        if (! $originalDateStart
            || $originalDateStart->greaterThan($today)
            || $originalDateStart->greaterThanOrEqualTo($changeStart)
            || (int) $this->previousValue($assignedHoursTemplate, 'recurrency') === 0) {
            $this->regenerateAll($assignedHoursTemplate);

            return;
        }

        $this->splitHistoricalTemplate($assignedHoursTemplate, $changeStart);
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

            if ($template->generated_until && $template->generated_until->greaterThan($newDateEnd)) {
                $template->forceFill(['generated_until' => $newDateEnd->toDateString()])->saveQuietly();
            }
        }

        $template->generateAssignedHours();
    }

    private function splitHistoricalTemplate(AssignedHoursTemplate $template, CarbonImmutable $changeStart): void
    {
        $futureHours = $template->assignedHours()->where('date', '>=', $changeStart->toDateString());
        $this->ensureHoursAreUnlocked(clone $futureHours);

        $newTemplate = $template->replicate(['id', 'created_at', 'updated_at']);
        $newTemplate->generated_until = null;

        if ((int) $newTemplate->recurrency > 0) {
            $newTemplate->date_start = $changeStart;
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
        $historicalEnd = $changeStart->subDay();
        if ($originalAttributes['date_end'] && CarbonImmutable::parse($originalAttributes['date_end'])->lessThan($historicalEnd)) {
            $historicalEnd = CarbonImmutable::parse($originalAttributes['date_end']);
        }
        $originalAttributes['date_end'] = $historicalEnd->toDateString();
        if ($originalAttributes['generated_until'] && CarbonImmutable::parse($originalAttributes['generated_until'])->greaterThan($historicalEnd)) {
            $originalAttributes['generated_until'] = $historicalEnd->toDateString();
        }

        $futureHours->delete();
        $template->syncOriginal();
        $template->setRawAttributes(array_replace($template->getAttributes(), $originalAttributes));
        $template->saveQuietly();
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
