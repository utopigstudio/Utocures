<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Characteristic extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Filterable, Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $appends = ['options_names'];

    /**
     * Get the options for the characteristic.
     */
    public function options()
    {
        return $this->hasMany(CharacteristicOption::class);
    }

    /**
     * Get the names of the options as a comma-separated string.
     */
    public function getOptionsNamesAttribute()
    {
        return $this->options->pluck('name')->join(', ');
    }
}