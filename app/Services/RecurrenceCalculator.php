<?php

namespace App\Services;

use App\Models\AssignedHoursTemplate;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class RecurrenceCalculator
{
    /**
     * @return Collection<int, CarbonImmutable>
     */
    public function dates(
        int $recurrency,
        array $daysOfWeek,
        CarbonInterface|string $rangeStart,
        CarbonInterface|string $rangeEnd,
        CarbonInterface|string $anchorDate,
    ): Collection {
        if (! in_array($recurrency, [
            AssignedHoursTemplate::RECURRENCY_TYPE_WEEK,
            AssignedHoursTemplate::RECURRENCY_TYPE_BIWEEK,
        ], true)) {
            throw new InvalidArgumentException("Unsupported recurrency type: {$recurrency}");
        }

        $start = $this->date($rangeStart);
        $end = $this->date($rangeEnd);
        $anchor = $this->date($anchorDate);
        $selectedDays = collect($daysOfWeek)
            ->map(fn ($day) => (int) $day)
            ->filter(fn (int $day) => $day >= CarbonInterface::SUNDAY && $day <= CarbonInterface::SATURDAY)
            ->unique()
            ->values();

        if ($selectedDays->isEmpty() || $start->greaterThan($end)) {
            return collect();
        }

        $dates = collect();

        for ($candidate = $start; $candidate->lessThanOrEqualTo($end); $candidate = $candidate->addDay()) {
            if ($candidate->lessThan($anchor) || ! $selectedDays->contains($candidate->dayOfWeek)) {
                continue;
            }

            if ($recurrency === AssignedHoursTemplate::RECURRENCY_TYPE_BIWEEK
                && ! $this->isBiweeklyMatch($candidate, $anchor)) {
                continue;
            }

            $dates->push($candidate);
        }

        return $dates;
    }

    public function occursOn(
        int $recurrency,
        array $daysOfWeek,
        CarbonInterface|string $date,
        CarbonInterface|string $anchorDate,
        CarbonInterface|string|null $dateEnd = null,
    ): bool {
        $candidate = $this->date($date);
        $anchor = $this->date($anchorDate);

        if ($candidate->lessThan($anchor)) {
            return false;
        }

        if ($dateEnd && $candidate->greaterThan($this->date($dateEnd))) {
            return false;
        }

        return $this->dates(
            recurrency: $recurrency,
            daysOfWeek: $daysOfWeek,
            rangeStart: $candidate,
            rangeEnd: $candidate,
            anchorDate: $anchor,
        )->isNotEmpty();
    }

    private function isBiweeklyMatch(CarbonImmutable $candidate, CarbonImmutable $anchor): bool
    {
        $anchorWeek = $anchor->startOfWeek(CarbonInterface::MONDAY);
        $candidateWeek = $candidate->startOfWeek(CarbonInterface::MONDAY);
        $weeksSinceAnchor = intdiv((int) $anchorWeek->diffInDays($candidateWeek), CarbonInterface::DAYS_PER_WEEK);

        return $weeksSinceAnchor % 2 === 0;
    }

    private function date(CarbonInterface|string $value): CarbonImmutable
    {
        return $value instanceof CarbonInterface
            ? CarbonImmutable::instance($value)->startOfDay()
            : CarbonImmutable::parse($value)->startOfDay();
    }
}
