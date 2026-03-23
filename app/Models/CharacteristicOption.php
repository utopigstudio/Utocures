<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class CharacteristicOption extends Model
{
    use HasFactory, HasUuids;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    /**
     * The characteristic that this option belongs to.
     */
    public function characteristic()
    {
        return $this->belongsTo(Characteristic::class);
    }
}
