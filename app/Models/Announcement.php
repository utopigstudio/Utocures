<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Announcement extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Filterable, Auditable;

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    public function getImageAttribute($value)
    {
        return $value ? Storage::url($value) : null;
    }
}