<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class AssignedHour extends Model
{
    use HasUuids, HasFactory;

    protected $guarded = ['id', 'created_at', 'updated_at'];
    
    protected $appends = ['programmed_hours', 'registered_hours'];

    protected $casts = [
        'time_start' => 'datetime:H:i',
        'time_end' => 'datetime:H:i',
        'date' => 'date:d/m/Y',
    ];

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
     * Get the employee substitute related to the assigned hour template.
     */
    public function employeeSubstitute(): BelongsTo
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
     * Get the assigned hour related to the assigned hour template.
     */
    public function assignedHourTemplate(): BelongsTo
    {
        return $this->belongsTo(AssignedHour::class);
    }

    /**
     * Get the time records for the assigned hour.
     */
    public function timeRecords()
    {
        return $this->hasMany(EmployeeTimeRecord::class);
    }

    /**
     * Get the programmed hours attribute.
     */
    public function getProgrammedHoursAttribute(): ?string
    {
        $start = Carbon::parse($this->time_start);
        $end = Carbon::parse($this->time_end);

        return $start->diffInMinutes($end, false);
    }

    /**
     * Get the registered hours attribute.
     */
    public function getRegisteredHoursAttribute(): ?string
    {
        return $this->timeRecords->sum('registered_hours');
    }
}
