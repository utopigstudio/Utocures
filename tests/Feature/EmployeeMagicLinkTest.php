<?php

use App\Http\Middleware\SetupCheck;
use App\Models\Employee;
use App\Models\User;
use App\Models\UserRole;
use App\Notifications\SendMagicLink;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use MagicLink\MagicLink;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
});

function magicLinkEmployee(bool $active = true): Employee
{
    $user = User::factory()->create(['email' => 'employee@test.es', 'role' => UserRole::EMPLOYEE, 'is_active' => $active]);

    return Employee::create(Employee::factory()->raw([
        'user_id' => $user->id, 'gender_id' => 1, 'country_id' => 'es',
    ]));
}

test('employee profiles include the account active state', function (bool $active) {
    $employee = magicLinkEmployee($active);
    $admin = User::factory()->create();

    $this->actingAs($admin)->get(route('employees.show', $employee))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('employee.user.is_active', $active));
})->with([true, false]);

test('administrators send a single use login link to the employee account', function () {
    $employee = magicLinkEmployee();
    $admin = User::factory()->create();
    Notification::fake();
    $linkCount = MagicLink::count();

    $this->actingAs($admin)->from(route('employees.show', $employee))
        ->post(route('employees.magic-link.store', $employee), ['email' => $admin->email])
        ->assertRedirect(route('employees.show', $employee))
        ->assertSessionHas('success', __('employees.magic_link_sent'));

    expect(MagicLink::count())->toBe($linkCount + 1);
    Notification::assertSentTo($employee->user, SendMagicLink::class);
    Notification::assertCount(1);
    $notification = Notification::sent($employee->user, SendMagicLink::class)->first();
    $url = $notification->toMail($employee->user)->actionUrl;
    $link = MagicLink::all()->first(fn (MagicLink $link): bool => $link->url === $url);
    expect($link->max_visits)->toBe(1);

    auth()->logout();
    $this->get($url)->assertRedirect();
    $this->assertAuthenticatedAs($employee->user);
});

test('inactive accounts do not receive magic links', function () {
    $employee = magicLinkEmployee(false);
    $admin = User::factory()->create();
    Notification::fake();
    $linkCount = MagicLink::count();

    $this->actingAs($admin)->post(route('employees.magic-link.store', $employee))
        ->assertSessionHasErrors(['magic_link' => __('employees.magic_link_inactive')]);

    Notification::assertNothingSent();
    expect(MagicLink::count())->toBe($linkCount);
});

test('non administrators cannot send employee magic links', function (string $role) {
    $employee = magicLinkEmployee();
    $user = User::factory()->create(['role' => $role]);
    Notification::fake();
    $linkCount = MagicLink::count();

    $this->actingAs($user)->post(route('employees.magic-link.store', $employee))->assertForbidden();

    Notification::assertNothingSent();
    expect(MagicLink::count())->toBe($linkCount);
})->with([UserRole::EMPLOYEE, UserRole::CLIENT]);

test('guests must sign in before sending employee magic links', function () {
    $employee = magicLinkEmployee();
    Notification::fake();

    $this->post(route('employees.magic-link.store', $employee))->assertRedirect(route('login'));

    Notification::assertNothingSent();
});

test('deleted employees cannot receive magic links', function () {
    $employee = magicLinkEmployee();
    $employee->delete();
    $admin = User::factory()->create();
    Notification::fake();

    $this->actingAs($admin)->post(route('employees.magic-link.store', $employee))->assertNotFound();

    Notification::assertNothingSent();
});
