<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use OwenIt\Auditing\Events\AuditCustom;

class LogSuccessfulLogin
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        /** @var \App\Models\User  */
        $user = $event->user;

        $user->auditEvent = 'login';
        $user->isCustomEvent = true;

        $user->auditCustomOld = [];
        $user->auditCustomNew = [
            'ip' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ];

        Event::dispatch(new AuditCustom($user));
    }
}
