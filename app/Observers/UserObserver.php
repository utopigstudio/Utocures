<?php

namespace App\Observers;

use App\Models\User;
use App\Notifications\UserCreatedNotification;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        $link = MagicLink::create(new LoginAction($user), null, 1)->url;
        $user->notify(new UserCreatedNotification($user->name, $link));
    }
}
