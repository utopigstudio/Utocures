<?php

namespace App\Models;

use App\Models\Traits\Downloadable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use App\Models\Traits\Filterable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Budget extends Model implements AuditableContract
{
    use HasFactory, HasUuids, Filterable, Auditable, Downloadable;

    public const STATUS_SENT = 1;
    public const STATUS_ACCEPTED = 2;
    public const STATUSES = ['budgets.draft', 'budgets.sent', 'budgets.accepted', 'budgets.rejected'];

    protected $guarded = ['id', 'created_at', 'updated_at'];
    protected $appends = ['status_name'];

    protected $casts = [
        'created_at' => 'date:d/m/Y',
        'due_date' => 'date:d/m/Y',
        'subtotal' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Get the client associated with the budget.
     */
    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get the lines for the budget.
     */
    public function lines()
    {
        return $this->hasMany(BudgetLine::class);
    }

    /**
     * Get the files associated with the client.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get the notes associated with the budget.
     */
    public function notes()
    {
        return $this->morphMany(Note::class, 'notable');
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
        return collect(self::STATUSES)->map(fn($item, $key) => (object)['id' => $key, 'name' => __($item)]);
    }

    /**
     * Scope a query to only include budgets of a given year.
     */
    public function scopeYear($query, $year)
    {
        return $query->whereYear('created_at', $year);
    }
}