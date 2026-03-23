<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class EmployeeTimeRecord extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Auditable;

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
     * Get the registered hours attribute.
     */
    public function getRegisteredHoursAttribute(): string
    {
        $start = $this->date_in->setTimeFromTimeString($this->time_in);
        $end = $this->date_out?->setTimeFromTimeString($this->time_out);
        
        return $start->diffInMinutes($end, false);
    }
}
