<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Client extends Model implements AuditableContract
{
    use Auditable, Filterable, HasFactory, HasUuids, Notifiable, SoftDeletes;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $casts = [
        'billing_recipient' => 'array',
        'birth_date' => 'date:d/m/Y',
        'is_partner' => 'boolean',
    ];

    protected $appends = ['gender', 'country'];

    /**
     * @return array{name: string, cif_nif: string, address: string, city: string, zip_code: string, country_id: string, email: ?string}
     */
    public function billingRecipientData(): array
    {
        return $this->billing_recipient ?? [
            'name' => $this->name,
            'cif_nif' => $this->cif_nif,
            'address' => $this->address,
            'city' => $this->city,
            'zip_code' => $this->zip_code,
            'country_id' => $this->country_id,
            'email' => $this->email,
        ];
    }

    /**
     * Get the user account associated with the client.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messageConversation(): HasOne
    {
        return $this->hasOne(MessageConversation::class);
    }

    public function messages(): HasManyThrough
    {
        return $this->hasManyThrough(
            Message::class,
            MessageConversation::class,
            'client_id',
            'conversation_id',
        );
    }

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
