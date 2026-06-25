<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class EmployeeStatusPeriod extends Model implements AuditableContract
{
    use HasFactory, HasUuids, SoftDeletes, Auditable;

    public const DISPLAY_DATETIME_FORMAT = 'd/m/Y H:i';

    public const INPUT_DATETIME_FORMAT = 'Y-m-d\TH:i';

    public const TYPE_VACATION = 'vacation';

    public const TYPE_SICK_LEAVE = 'sick_leave';

    public const TYPE_ABSENCE = 'absence';

    public const TYPE_PERMISSION = 'permission';

    public const TYPES = [
        self::TYPE_VACATION => [
            'label_key' => 'employees.status_type_vacation',
            'color' => 'emerald',
        ],
        self::TYPE_SICK_LEAVE => [
            'label_key' => 'employees.status_type_sick_leave',
            'color' => 'rose',
        ],
        self::TYPE_ABSENCE => [
            'label_key' => 'employees.status_type_absence',
            'color' => 'slate',
        ],
        self::TYPE_PERMISSION => [
            'label_key' => 'employees.status_type_permission',
            'color' => 'amber',
        ],
    ];

    protected $guarded = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $appends = [
        'label',
        'color',
        'start_at_formatted',
        'end_at_formatted',
        'start_at_input',
        'end_at_input',
        'updated_at_formatted',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getLabelAttribute(): string
    {
        $meta = static::TYPES[$this->type] ?? null;

        if (!$meta) {
            return ucfirst(str_replace('_', ' ', $this->type));
        }

        return __($meta['label_key']);
    }

    public function getColorAttribute(): string
    {
        return static::TYPES[$this->type]['color'] ?? 'gray';
    }

    public function getStartAtFormattedAttribute(): ?string
    {
        return $this->start_at?->format(self::DISPLAY_DATETIME_FORMAT);
    }

    public function getEndAtFormattedAttribute(): ?string
    {
        return $this->end_at?->format(self::DISPLAY_DATETIME_FORMAT);
    }

    public function getStartAtInputAttribute(): ?string
    {
        return $this->start_at?->format(self::INPUT_DATETIME_FORMAT);
    }

    public function getEndAtInputAttribute(): ?string
    {
        return $this->end_at?->format(self::INPUT_DATETIME_FORMAT);
    }

    public function getUpdatedAtFormattedAttribute(): ?string
    {
        return $this->updated_at?->format(self::DISPLAY_DATETIME_FORMAT);
    }
}
