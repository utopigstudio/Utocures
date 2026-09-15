<?php

namespace App\Http\Controllers;

use App\Http\Requests\Client\ClientAccessStoreRequest;
use App\Http\Requests\Client\ClientAccessUpdateRequest;
use App\Models\Client;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\ClientAccessInvitationNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClientAccessController extends Controller
{
    public function store(ClientAccessStoreRequest $request, Client $client): RedirectResponse
    {
        if ($client->user) {
            throw ValidationException::withMessages([
                'email' => __('clients.access_already_exists'),
            ]);
        }

        $user = DB::transaction(function () use ($client, $request) {
            $user = User::withoutEvents(fn () => User::create([
                'name' => $client->name,
                'email' => $request->validated('email'),
                'role' => UserRole::CLIENT,
                'password' => Str::random(40),
                'is_active' => true,
            ]));

            $client->user()->associate($user);
            $client->save();

            return $user;
        });

        $this->sendInvitation($user);

        return back()->with('success', __('clients.access_created'));
    }

    public function update(ClientAccessUpdateRequest $request, Client $client): RedirectResponse
    {
        $user = $this->userFor($client);
        $previousEmail = $user->email;

        DB::transaction(function () use ($request, $user) {
            $user->update([
                'email' => $request->validated('email'),
                'role' => UserRole::CLIENT,
            ]);
        });

        if ($previousEmail !== $user->email) {
            DB::table('password_reset_tokens')->where('email', $previousEmail)->delete();
            $this->sendPasswordSetupLink($user);
        }

        return back()->with('success', __('clients.access_updated'));
    }

    public function invite(Client $client): RedirectResponse
    {
        $user = $this->activeUserFor($client);

        if ($user->invitation_accepted_at !== null) {
            throw ValidationException::withMessages([
                'email' => __('clients.invitation_already_accepted'),
            ]);
        }

        $this->sendInvitation($user);

        return back()->with('success', __('clients.invitation_sent'));
    }

    public function resetPassword(Client $client): RedirectResponse
    {
        $user = $this->activeUserFor($client);

        $this->sendPasswordResetLink($user);

        return back()->with('success', __('clients.password_reset_sent'));
    }

    public function activate(Client $client): RedirectResponse
    {
        $this->userFor($client)->activate();

        return back()->with('success', __('clients.access_enabled'));
    }

    public function destroy(Client $client): RedirectResponse
    {
        $this->userFor($client)->deactivate();

        return back()->with('success', __('clients.access_disabled'));
    }

    private function sendInvitation(User $user): void
    {
        $token = Password::broker()->createToken($user);
        $user->notify(new ClientAccessInvitationNotification($user->name, $token));
    }

    private function sendPasswordSetupLink(User $user): void
    {
        if ($user->invitation_accepted_at === null) {
            $this->sendInvitation($user);

            return;
        }

        $this->sendPasswordResetLink($user);
    }

    private function sendPasswordResetLink(User $user): void
    {
        Password::broker()->deleteToken($user);
        $status = Password::sendResetLink(['email' => $user->email]);

        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }
    }

    private function activeUserFor(Client $client): User
    {
        $user = $this->userFor($client);

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => __('clients.access_inactive'),
            ]);
        }

        return $user;
    }

    private function userFor(Client $client): User
    {
        abort_unless($client->user, 404);

        return $client->user;
    }
}
