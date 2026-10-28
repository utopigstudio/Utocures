<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmployeeDocumentFolder extends Model
{
    use HasUuids;

    protected $fillable = ['name'];

    public function files(): HasMany
    {
        return $this->hasMany(File::class);
    }
}
