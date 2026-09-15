<?php

use App\Http\Middleware\SetupCheck;
use App\Models\Client;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\ClientAccessInvitationNotification;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

function clientWithPortalAccess(array $userAttributes = []): Client
{
    $user = User::factory()->create(array_merge([
        'email' => 'old-access@example.com',
        'role' => UserRole::CLIENT,
    ], $userAttributes));

    return Client::query()->create([
        'user_id' => $user->id,
        'name' => 'Test client',
        'gender_id' => 1,
        'cif_nif' => fake()->unique()->dni(),
        'email' => 'contact@example.com',
        'phone' => '600000000',
        'address' => 'Test street',
        'city' => 'Test city',
        'zip_code' => '08001',
        'country_id' => 'ES',
    ]);
}

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    Notification::fake();
});

test('changing the access email sends a password setup invitation to an unaccepted client account', function () {
    $client = clientWithPortalAccess();
    $user = $client->user;
    $previousEmail = $user->email;
    $newEmail = 'new-access@example.com';

    Password::broker()->createToken($user);

    $response = $this->put(route('clients.access.update', $client), [
        'email' => $newEmail,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe($newEmail);
    expect(DB::table('password_reset_tokens')->where('email', $previousEmail)->exists())->toBeFalse();
    expect(DB::table('password_reset_tokens')->where('email', $newEmail)->exists())->toBeTrue();

    Notification::assertSentTo($user, ClientAccessInvitationNotification::class);
    Notification::assertNotSentTo($user, ResetPassword::class);
});

test('changing the access email sends a password reset to an accepted client account', function () {
    $client = clientWithPortalAccess(['invitation_accepted_at' => now()]);
    $user = $client->user;
    $newEmail = 'accepted-new-access@example.com';

    $response = $this->put(route('clients.access.update', $client), [
        'email' => $newEmail,
    ]);

    $response->assertRedirect()->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe($newEmail);

    Notification::assertSentTo($user, ResetPassword::class);
    Notification::assertNotSentTo($user, ClientAccessInvitationNotification::class);
});
