<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class Note extends Model implements AuditableContract
{
    use Auditable, Filterable, HasFactory, HasUuids;

    public const TYPE_GENERAL = 'general';

    public const TYPE_INCIDENT = 'incident';

    protected $fillable = ['content', 'user_id', 'type', 'employee_time_record_id'];

    /**
     * Get the parent noteable model.
     */
    public function noteable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the linked employee time record for incident notes.
     */
    public function employeeTimeRecord(): BelongsTo
    {
        return $this->belongsTo(EmployeeTimeRecord::class);
    }

    /**
     * Get the user that created the note.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the admin notifications linked to the note.
     */
    public function adminNotifications(): MorphMany
    {
        return $this->morphMany(AdminNotification::class, 'notifiable');
    }
}
