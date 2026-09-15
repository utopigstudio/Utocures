<?php

namespace App\Models;

use App\Models\Traits\Filterable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\WebPush\HasPushSubscriptions;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class User extends Authenticatable implements AuditableContract
{
    use Auditable, Filterable, HasFactory, HasPushSubscriptions, HasUuids, Notifiable;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'password',
        'is_active',
        'avatar',
        'locale',
        'last_seen_changelog_version',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_active' => 'boolean',
            'invitation_accepted_at' => 'datetime',
        ];
    }

    /**
     * The attributes that should be audited.
     */
    protected $auditInclude = [
        'name',
        'email',
        'role',
        'is_active',
        'avatar',
        'locale',
        'password',
    ];

    /**
     * Get the employee record associated with the user.
     */
    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    /**
     * Get the client record associated with the user.
     */
    public function client(): HasOne
    {
        return $this->hasOne(Client::class);
    }

    public function sentMessages(): HasMany
    {
        return $this->hasMany(Message::class, 'sender_user_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isEmployee(): bool
    {
        return $this->role === UserRole::EMPLOYEE;
    }

    public function isClient(): bool
    {
        return $this->role === UserRole::CLIENT;
    }

    public function deactivate(): void
    {
        $this->update(['is_active' => false]);
        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $this->getKey())
            ->delete();
        Password::broker()->deleteToken($this);
    }

    public function activate(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Accessor for avatar attribute to return full URL.
     */
    public function getAvatarAttribute($value)
    {
        return $value ? Storage::url($value) : null;
    }
}
