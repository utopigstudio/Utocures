<?php

namespace App\Models;

use Carbon\CarbonInterface;
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

    public const STATUS_ACTIVE = 'active';

    public const STATUSES = [
        self::STATUS_ACTIVE => [
            'label_key' => 'employees.status_active',
        ],
        EmployeeStatusPeriod::TYPE_VACATION => [
            'label_key' => 'employees.status_type_vacation',
        ],
        EmployeeStatusPeriod::TYPE_SICK_LEAVE => [
            'label_key' => 'employees.status_type_sick_leave',
        ],
        EmployeeStatusPeriod::TYPE_ABSENCE => [
            'label_key' => 'employees.status_type_absence',
        ],
        EmployeeStatusPeriod::TYPE_PERMISSION => [
            'label_key' => 'employees.status_type_permission',
        ],
    ];

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
     * Get the temporary status periods associated with the employee.
     */
    public function statusPeriods(): HasMany
    {
        return $this->hasMany(EmployeeStatusPeriod::class);
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

    public function activeStatusPeriodAt(CarbonInterface $moment): ?EmployeeStatusPeriod
    {
        return $this->statusPeriods()
            ->where('start_at', '<=', $moment)
            ->where('end_at', '>=', $moment)
            ->orderByDesc('start_at')
            ->first();
    }

    public function nextStatusPeriodAfter(CarbonInterface $moment): ?EmployeeStatusPeriod
    {
        return $this->statusPeriods()
            ->where('start_at', '>', $moment)
            ->orderBy('start_at')
            ->first();
    }

    public function statusForMoment(CarbonInterface $moment): string
    {
        return $this->activeStatusPeriodAt($moment)?->type ?? self::STATUS_ACTIVE;
    }

    public static function statusLabel(string $status): string
    {
        $meta = static::STATUSES[$status] ?? null;

        if (!$meta) {
            return ucfirst(str_replace('_', ' ', $status));
        }

        return __($meta['label_key']);
    }

    public function delete()
    {
        if ($this->user) {
            $this->user->update(['is_active' => false]);
        }

        return parent::delete();
    }
}