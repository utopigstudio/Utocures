<?php

namespace App\Models;

use App\Scopes\ActiveTemplates;
use App\Services\RecurrenceCalculator;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AssignedHoursTemplate extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    public array $scheduleOriginalAttributes = [];

    const DAYS_GENERATE = 90;

    const RECURRENCY_TYPE_WEEK = 1;

    const RECURRENCY_TYPE_BIWEEK = 2;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $appends = ['days_names'];

    protected $casts = [
        'days_of_week' => 'array',
        'time_start' => 'date:H:i',
        'time_end' => 'date:H:i',
        'date_start' => 'date:d/m/Y',
        'recurrence_anchor_date' => 'date:d/m/Y',
        'date_end' => 'date:d/m/Y',
        'generated_until' => 'date:d/m/Y',
        'date' => 'date:d/m/Y',
    ];

    protected $daysOfWeek = [
        'sunday',
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
    ];

    protected static function booted()
    {
        static::addGlobalScope(new ActiveTemplates);
    }

    /**
     * Get the client related to the assigned hour template.
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the employee related to the assigned hour template.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the service related to the assigned hour template.
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the assigned hours related to the assigned hour template.
     */
    public function assignedHours(): HasMany
    {
        return $this->hasMany(AssignedHour::class);
    }

    /**
     * Get the country name attribute.
     */
    public function getDaysNamesAttribute(): ?array
    {
        if (empty($this->days_of_week)) {
            return null;
        }

        $daysNames = [];
        foreach ($this->days_of_week as $day) {
            if (isset($this->daysOfWeek[$day])) {
                $daysNames[] = __('layout.'.$this->daysOfWeek[$day]);
            }
        }

        return $daysNames;
    }

    public function generateAssignedHours(): void
    {
        if ($this->recurrency > 0) {
            $this->generateRecurrentAssignedHours();

            return;
        }

        $this->generateNonRecurrentAssignedHours();
    }

    private function generateNonRecurrentAssignedHours(): void
    {
        if (! $this->date) {
            return;
        }

        $exists = AssignedHour::query()
            ->where('employee_id', $this->employee_id)
            ->where('date', $this->date->format('Y-m-d'))
            ->where('time_start', '<', $this->time_end->format('H:i:s'))
            ->where('time_end', '>', $this->time_start->format('H:i:s'))
            ->exists();

        if ($exists) {
            return;
        }

        $assignedHour = [
            'id' => Str::uuid(),
            'client_id' => $this->client_id,
            'employee_id' => $this->employee_id,
            'service_id' => $this->service_id,
            'assigned_hours_template_id' => $this->id,
            'date' => $this->date,
            'time_start' => $this->time_start,
            'time_end' => $this->time_end,
        ];

        $this->assignedHours()->create($assignedHour);
    }

    private function generateRecurrentAssignedHours(): void
    {
        $days = array_map('intval', $this->days_of_week ?? []);
        if (empty($days)) {
            return;
        }

        $today = CarbonImmutable::today();
        $end = $today->addDays(self::DAYS_GENERATE);
        $anchor = $this->recurrenceAnchorDate();
        $effectiveStart = $this->date_start
            ? CarbonImmutable::instance($this->date_start)
            : $anchor;
        $start = $today->greaterThan($effectiveStart) ? $today : $effectiveStart;

        if ($this->generated_until) {
            $generatedUntil = CarbonImmutable::instance($this->generated_until);
            if ($generatedUntil->greaterThanOrEqualTo($start)) {
                $start = $generatedUntil->addDay();
            }
        } else {
            $lastAssignedDate = $this->assignedHours()->max('date');
            if ($lastAssignedDate) {
                $legacyStart = CarbonImmutable::parse($lastAssignedDate)->addDay();
                if ($legacyStart->greaterThan($start)) {
                    $start = $legacyStart;
                }
            }
        }

        if ($this->date_end && CarbonImmutable::instance($this->date_end)->lessThan($end)) {
            $end = CarbonImmutable::instance($this->date_end);
        }

        if ($start->greaterThan($end)) {
            return;
        }

        $occupiedDates = AssignedHour::query()
            ->where('employee_id', $this->employee_id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('time_start', '<', $this->time_end->format('H:i:s'))
            ->where('time_end', '>', $this->time_start->format('H:i:s'))
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date)->toDateString())
            ->flip();

        $assignedHours = app(RecurrenceCalculator::class)
            ->dates(
                recurrency: (int) $this->recurrency,
                daysOfWeek: $days,
                rangeStart: $start,
                rangeEnd: $end,
                anchorDate: $anchor,
            )
            ->reject(fn (CarbonImmutable $date) => $occupiedDates->has($date->toDateString()))
            ->map(fn (CarbonImmutable $date) => [
                'id' => Str::uuid(),
                'client_id' => $this->client_id,
                'employee_id' => $this->employee_id,
                'service_id' => $this->service_id,
                'assigned_hours_template_id' => $this->id,
                'date' => $date->toDateString(),
                'time_start' => $this->time_start->format('H:i'),
                'time_end' => $this->time_end->format('H:i'),
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all();

        if (! empty($assignedHours)) {
            $this->assignedHours()->insert($assignedHours);
        }

        $this->forceFill(['generated_until' => $end->toDateString()])->saveQuietly();
    }

    public function recurrenceAnchorDate(): CarbonImmutable
    {
        $anchor = $this->recurrence_anchor_date
            ?? $this->date_start
            ?? $this->created_at
            ?? today();

        return CarbonImmutable::instance($anchor)->startOfDay();
    }
}
