<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssignedService extends Model
{
    use HasFactory;

    protected $fillable = ['service_id'];
    public $timestamps = false;

    /**
     * Get the parent serviceable model.
     */
    public function serviceable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the service associated with the assigned service.
     */
    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
