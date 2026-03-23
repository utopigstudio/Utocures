<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AvailableHour extends Model implements AuditableContract
{
    use HasUuids, HasFactory, Auditable;

    protected $fillable = ['day_of_week', 'time_start', 'time_end'];

    protected $casts = [
        'time_start' => 'datetime:H:i',
        'time_end' => 'datetime:H:i',
    ];

    /**
     * Get the parent hourable model.
     */
    public function hourable(): MorphTo
    {
        return $this->morphTo();
    }
}
