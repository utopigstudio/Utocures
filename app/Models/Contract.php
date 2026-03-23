<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\Traits\Downloadable;
use App\Models\Traits\Filterable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Contract extends Model implements AuditableContract
{
    use HasFactory, HasUuids, HasUuids, Filterable, Auditable, Downloadable;

    public const STATUSES = [
        0 => 'Draft',
        1 => 'Sent',
        2 => 'Accepted',
        3 => 'Rejected',
    ];

    protected $appends = ['status_name'];

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'created_at' => 'date:d/m/Y',
        'date_start' => 'date:d/m/Y',
        'date_end' => 'date:d/m/Y',
    ];

    /**
     * Get the client associated with the contract.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the notes associated with the contract.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get the files associated with the client.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get the status name.
     */
    protected function statusName(): Attribute
    {
        return Attribute::make(
            get: fn ($value, $attributes) => self::getStatuses()->firstWhere('id', $attributes['status'])->name ?? null,
        );
    }

    /**
     * Get all available statuses.
     */
    public static function getStatuses(): Collection
    {
        return collect(self::STATUSES)->map(fn($item, $key) => (object)['id' => $key, 'name' => $item]);
    }

    /**
     * Scope a query to only include budgets of a given year.
     */
    public function scopeYear($query, $year)
    {
        return $query->whereYear('created_at', $year);
    }
}