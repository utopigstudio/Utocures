<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Announcement extends Model implements AuditableContract
{
    use Auditable, Filterable, HasFactory, HasUuids;

    public const TO_EMPLOYEE = 'employee';

    public const TO_ADMIN = 'admin';

    public const TO_CLIENT = 'client';

    public const TO_BOTHS = 'boths';

    public const AUDIENCES = [
        self::TO_EMPLOYEE,
        self::TO_ADMIN,
        self::TO_CLIENT,
        self::TO_BOTHS,
    ];

    protected $attributes = [
        'to' => self::TO_EMPLOYEE,
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeVisibleTo(Builder $query, string $audience): Builder
    {
        $audiences = [$audience];

        if (in_array($audience, [self::TO_EMPLOYEE, self::TO_ADMIN], true)) {
            $audiences[] = self::TO_BOTHS;
        }

        return $query->whereIn('to', $audiences);
    }

    public function getImageAttribute($value)
    {
        return $value ? Storage::url($value) : null;
    }
}
