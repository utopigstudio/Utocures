<?php

namespace App\Models;

use App\Services\IconService;
use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Collection;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Service extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Filterable, Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $appends = ['icon', 'color'];

    public const COLORS = ['#DCFCE7', '#FFEDD5', '#FCE7F3', '#FEE2E2', '#ECFCCB', '#E0E7FF', '#FEF3C7'];

    /**
     * Get the service tasks for the service.
     */
    public function tasks()
    {
        return $this->hasMany(ServiceTask::class);
    }

    /**
     * Get all available colors.
     */
    public static function getColors(): Collection
    {
        return collect(self::COLORS)->map(fn($item) => (object)['id' => $item, 'name' => $item]);
    }

    /**
     * Get the icon for the service.
     */
    protected function icon(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['icon_slug']) ? (new IconService(['healthicons']))->svg($attributes['icon_slug']) : null,
        );
    }

    /**
     * Get the color name.
     */
    protected function color(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => isset($attributes['color']) ? self::getColors()->firstWhere('id', $attributes['color'])->name : null,
        );
    }
}