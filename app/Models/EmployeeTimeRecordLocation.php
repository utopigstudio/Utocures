<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeTimeRecordLocation extends Model
{
    use HasFactory, HasUuids;

    public const EVENT_IN = 'in';

    public const EVENT_OUT = 'out';

    public const EVENTS = [
        self::EVENT_IN,
        self::EVENT_OUT,
    ];

    protected $guarded = ['id'];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'accuracy_meters' => 'decimal:2',
        'position_captured_at' => 'datetime',
    ];

    public function timeRecord(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimeRecord::class, 'employee_time_record_id');
    }
}
