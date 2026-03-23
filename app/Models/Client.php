<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\Filterable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;
use Illuminate\Notifications\Notifiable;

class Client extends Model implements AuditableContract
{
    use HasFactory, HasUuids, SoftDeletes, Filterable, Auditable, Notifiable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'birth_date' => 'date:d/m/Y',
        'is_partner' => 'boolean',
    ];

    protected $appends = ['gender', 'country'];

    /**
     * Get the characteristics options associated with the client.
     */
    public function assignedCharacteristics(): MorphToMany
    {
        return $this->morphToMany(CharacteristicOption::class, 'characterizable', 'assigned_characteristics', 'characterizable_id', 'characteristic_option_id')->with('characteristic');
    }

    /**
     * Get the services associated with the client.
     */
    public function services(): MorphToMany
    {
        return $this->morphToMany(Service::class, 'serviceable', 'assigned_services', 'serviceable_id', 'service_id');
    }

    /**
     * Get the files associated with the client.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get the notes associated with the client.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get the contracts associated with the client.
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(Contract::class);
    }

    /**
     * Get the budgets associated with the client.
     */
    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    /**
     * Get the invoices associated with the client.
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    /**
     * Get hours templates assigned to the client.
     */
    public function assignedHoursTemplates(): HasMany
    {
        return $this->hasMany(AssignedHoursTemplate::class)
            ->where(function ($query) {
                $query->whereNull('date_end')
                    ->orWhere('date_end', '>=', now()->toDateString());
            })
            ->whereHas('employee.user', function ($query) {
                $query->where('is_active', true);
            });
    }

    /**
     * Get hours assigned to the client.
     */
    public function assignedHours(): HasMany
    {
        return $this->hasMany(AssignedHour::class);
    }

    /**
     * Get the gender name attribute.
     */
    public function getGenderAttribute(): ?object
    {
        return Gender::find($this->gender_id);
    }

    /**
     * Get the country name attribute.
     */
    public function getCountryAttribute(): ?object
    {
        return Country::find($this->country_id);
    }
}