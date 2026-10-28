<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class EmployeeTimeRecord extends Model implements AuditableContract
{
    use Auditable, HasFactory, HasUuids;

    protected $guarded = ['id'];

    protected $appends = ['registered_hours'];

    protected $casts = [
        'date_in' => 'date:d-m-Y',
        'date_out' => 'date:d-m-Y',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Get the user associated with the action event.
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the assigned hours associated with the time record.
     */
    public function assignedHour(): BelongsTo
    {
        return $this->belongsTo(AssignedHour::class);
    }

    /**
     * Get the incident notes linked to the time record.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(EmployeeTimeRecordLocation::class);
    }

    public function locationIn(): HasOne
    {
        return $this->hasOne(EmployeeTimeRecordLocation::class)->where('event_type', EmployeeTimeRecordLocation::EVENT_IN);
    }

    public function locationOut(): HasOne
    {
        return $this->hasOne(EmployeeTimeRecordLocation::class)->where('event_type', EmployeeTimeRecordLocation::EVENT_OUT);
    }

    /**
     * Get the registered hours attribute.
     */
    public function getRegisteredHoursAttribute(): string
    {
        if (! $this->date_out || ! $this->time_out) {
            return '0';
        }

        $start = $this->date_in->setTimeFromTimeString($this->time_in);
        $end = $this->date_out?->setTimeFromTimeString($this->time_out);

        return $start->diffInMinutes($end, false);
    }
}
