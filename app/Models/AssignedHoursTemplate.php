<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use App\Scopes\ActiveTemplates;

class AssignedHoursTemplate extends Model implements AuditableContract
{
    use HasUuids, HasFactory, Auditable;

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
        'date_end' => 'date:d/m/Y',
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
                $daysNames[] = __('layout.' . $this->daysOfWeek[$day]);
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
        if (!$this->date) {
            return;
        }

        $exists = $this->assignedHours()
            ->where('date', $this->date->format('Y-m-d'))
            ->where('time_start', $this->time_start)
            ->where('time_end', $this->time_end)
            ->exists();

        if ($exists) {
            return;
        }

        $assignedHour = [
            'id' => \Illuminate\Support\Str::uuid(),
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
        $start = Carbon::today();
        $end = $start->copy()->addDays(self::DAYS_GENERATE);
        if ($this->date_start) {
            if ($this->date_start->greaterThan($end)) {
                return;
            }
            if ($this->date_start->greaterThan($start)) {
                $start = $this->date_start->copy();
            }
        }

        if ($this->assignedHours()->count() != 0) {
            $lastAssignedHour = $this->assignedHours()->orderBy('date', 'desc')->first();
            $start = $lastAssignedHour->date->addDay();
        }

        if ($this->date_end) {
            $templateEnd = $this->date_end;
            if ($templateEnd->lessThan($start)) {
                return;
            }
            if ($templateEnd->lessThan($end)) {
                $end = $templateEnd;
            }
        }

        if ($start->greaterThan($end)) {
            return;
        }

        $days = array_map('intval', $this->days_of_week ?? []);
        if (empty($days)) {
            return;
        }

        $assignedHours = [];

        $date = $start->copy();
        $lastOfDays = max($days);

        while ($date->lessThanOrEqualTo($end)) {
            while ($date->dayOfWeek <= $lastOfDays) {
                if ($date->greaterThan($end)) {
                    break 2;
                }
                if (in_array($date->dayOfWeek, $days)) {
                    $assignedHours[] = [
                        'id' => \Illuminate\Support\Str::uuid(),
                        'client_id' => $this->client_id,
                        'employee_id' => $this->employee_id,
                        'service_id' => $this->service_id,
                        'assigned_hours_template_id' => $this->id,
                        'date' => $date->format('Y-m-d'),
                        'time_start' => $this->time_start,
                        'time_end' => $this->time_end,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                $date->addDay();
                if ($date->dayOfWeek == 0) {
                    break;
                }
            }

            if ($this->recurrency == self::RECURRENCY_TYPE_WEEK) {
                $date->addWeek()->startOfWeek();
            } elseif ($this->recurrency == self::RECURRENCY_TYPE_BIWEEK) {
                $date->addWeeks(2)->startOfWeek();
            }
        }

        if (!empty($assignedHours)) {
            $this->assignedHours()->insert($assignedHours);
        }
    }

}
