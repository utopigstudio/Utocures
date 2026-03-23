<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AssignedCharacteristic extends Model
{
    use HasFactory;

    protected $fillable = ['characteristic_option_id'];

    /**
     * Get the parent characterizable model.
     */
    public function characterizable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * The characteristic option belongs to.
     */
    public function characteristicOption()
    {
        return $this->belongsTo(CharacteristicOption::class);
    }
}
