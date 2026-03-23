<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use App\Models\Traits\Filterable;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Employee extends Model implements AuditableContract
{
    use HasFactory, HasUuids, SoftDeletes, Filterable, Auditable;

    protected $guarded = ['id', 'created_at', 'updated_at'];

    protected $appends = ['gender', 'country'];

    protected $casts = [
        'birth_date' => 'date:d/m/Y',
        'hire_date' => 'date:d/m/Y',
    ];

    /**
     * Get the files associated with the employee.
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get the characteristics options associated with the employee.
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
     * Get the notes associated with the employee.
     */
    public function notes(): MorphMany
    {
        return $this->morphMany(Note::class, 'noteable');
    }

    /**
     * Get hours assigned to the employee.
     */
    public function assignedHours(): HasMany
    {
        return $this->hasMany(AssignedHour::class);
    }

    /**
     * Get hours templates assigned to the employee.
     */
    public function assignedHoursTemplates(): HasMany
    {
        return $this->hasMany(AssignedHoursTemplate::class);
    }

    /**
     * Get available hours associated with the employee.
     */
    public function availableHours(): MorphMany
    {
        return $this->morphMany(AvailableHour::class, 'hourable');
    }

    /**
     * Get the user that owns the employee.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the time records associated with the employee.
     */
    public function timeRecords(): HasMany
    {
        return $this->hasMany(EmployeeTimeRecord::class);
    }

    /**
     * Get the gender name attribute.
     */
    public function getGenderAttribute(): ?object
    {
        return Gender::find($this->gender_id);
    }

    /**
     * Get the programmed hours formatted attribute.
     */
    public function getProgrammedHoursAttribute(): ?string
    {
        $totalMinutes = $this->assignedHours->append('programmed_hours')->sum('programmed_hours');

        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }

    /**
     * Get the registered hours attribute.
     */
    public function getRegisteredHoursAttribute(): ?string
    {
        $totalMinutes = $this->timeRecords->append('registered_hours')->sum('registered_hours');

        return sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60);
    }

    /**
     * Get the country name attribute.
     */
    public function getCountryAttribute(): ?object
    {
        return Country::find($this->country_id);
    }

    public function delete()
    {
        if ($this->user) {
            $this->user->update(['is_active' => false]);
        }

        return parent::delete();
    }
}