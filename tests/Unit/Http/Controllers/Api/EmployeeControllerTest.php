<?php

use App\Http\Middleware\SetupCheck;
use App\Models\AssignedHour;
use App\Models\AssignedHoursTemplate;
use App\Models\AvailableHour;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
    $this->travelTo(now()->setDate(2026, 10, 27)->startOfDay());
});

function schedulingOptionsFixture(): array
{
    $user = User::factory()->create(['email' => 'employee@test.es', 'role' => UserRole::EMPLOYEE]);
    $employee = Employee::factory()->make(['user_id' => $user->id, 'gender_id' => 1, 'country_id' => 'ES']);
    $employee->saveQuietly();
    $client = Client::factory()->make(['gender_id' => 1, 'country_id' => 'ES']);
    $client->saveQuietly();
    $service = Service::factory()->make(['name' => 'Cleaning']);
    $service->saveQuietly();
    $employee->services()->attach($service);
    AvailableHour::factory()->for($employee, 'hourable')->create(['day_of_week' => 2]);
    $template = AssignedHoursTemplate::factory()->create([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'recurrency' => 0, 'date' => '2026-11-03', 'date_start' => null, 'date_end' => null,
        'time_start' => '10:00', 'time_end' => '12:00', 'days_of_week' => null,
    ]);

    AssignedHour::where('assigned_hours_template_id', $template->id)->update([
        'date' => '2026-11-03', 'time_start' => '10:00:00', 'time_end' => '12:00:00',
    ]);

    return [$employee, $client, $service, $template];
}

function employeeOptionsQuery(Service $service, array $overrides = []): string
{
    return route('api.employees.index', array_merge([
        'service_id' => $service->id, 'recurrency' => '0', 'date' => '2026-11-03',
        'time_start' => '11:00', 'time_end' => '13:00',
    ], $overrides));
}

test('returns the conflicting service and first date without duplicating generated hours', function () {
    [$employee, $client, $service, $template] = schedulingOptionsFixture();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->getJson(employeeOptionsQuery($service))->assertOk()
        ->assertJsonCount(0, 'data')->assertJsonCount(1, 'unavailable')
        ->assertJsonPath('unavailable.0.id', $employee->id)
        ->assertJsonPath('unavailable.0.is_selectable', false)
        ->assertJsonCount(1, 'unavailable.0.conflicts')
        ->assertJsonPath('unavailable.0.conflicts.0.client_id', $client->id)
        ->assertJsonPath('unavailable.0.conflicts.0.service_name', 'Cleaning')
        ->assertJsonPath('unavailable.0.conflicts.0.date', '2026-11-03')
        ->assertJsonPath('unavailable.0.conflicts.0.time_start', '10:00');
});

test('keeps consecutive schedules available', function () {
    [$employee, , $service] = schedulingOptionsFixture();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->getJson(employeeOptionsQuery($service, ['time_start' => '12:00']))->assertOk()
        ->assertJsonPath('data.0.id', $employee->id)->assertJsonCount(0, 'unavailable');
});

test('explains missing habitual availability', function () {
    [$employee, , $service] = schedulingOptionsFixture();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->getJson(employeeOptionsQuery($service, ['time_start' => '18:00', 'time_end' => '19:00']))->assertOk()
        ->assertJsonPath('unavailable.0.id', $employee->id)
        ->assertJsonPath('unavailable.0.outside_availability', true)
        ->assertJsonPath('unavailable.0.unavailable_days', [2])
        ->assertJsonCount(0, 'unavailable.0.conflicts');
});

test('ignores the template and its generated hours when editing a planning', function () {
    [$employee, , $service, $template] = schedulingOptionsFixture();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->getJson(employeeOptionsQuery($service, ['event_id' => $template->id]))->assertOk()
        ->assertJsonPath('data.0.id', $employee->id)->assertJsonCount(0, 'unavailable');
});

test('reports a modified occurrence when its template has a different schedule', function () {
    [$employee, , $service, $template] = schedulingOptionsFixture();
    $hour = AssignedHour::where('assigned_hours_template_id', $template->id)->firstOrFail();
    AssignedHour::whereKey($hour->id)->update(['time_start' => '14:00:00', 'time_end' => '16:00:00']);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->getJson(employeeOptionsQuery($service, ['time_start' => '15:00', 'time_end' => '17:00']))->assertOk()
        ->assertJsonPath('unavailable.0.conflicts.0.id', $hour->id)
        ->assertJsonPath('unavailable.0.conflicts.0.type', 'assigned_hour');
});

test('respects alternate weeks and reports the first matching recurrence', function () {
    [$employee, , $service, $template] = schedulingOptionsFixture();
    $template->update(['recurrency' => 2, 'date' => null, 'date_start' => '2026-11-03',
        'recurrence_anchor_date' => '2026-11-03', 'days_of_week' => ['2']]);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->getJson(employeeOptionsQuery($service, ['date' => '2026-11-10']))->assertOk()
        ->assertJsonPath('data.0.id', $employee->id);
    $this->getJson(employeeOptionsQuery($service, ['recurrency' => '1', 'days_of_week' => [2],
        'date_start' => '2026-11-10', 'date_end' => '2026-11-24']))->assertOk()
        ->assertJsonPath('unavailable.0.conflicts.0.date', '2026-11-17');
});

test('keeps absence warnings selectable without a schedule conflict', function () {
    [$employee, , $service] = schedulingOptionsFixture();
    $employee->statusPeriods()->create(['type' => 'absence', 'start_at' => '2026-11-03 13:00', 'end_at' => '2026-11-03 18:00']);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $response = $this->getJson(employeeOptionsQuery($service, ['time_start' => '14:00', 'time_end' => '16:00']));
    $response->assertOk()->assertJsonPath('data.0.is_selectable', true)->assertJsonCount(0, 'unavailable');
    expect($response->json('data.0.unavailability_reason'))->toBeString()->not->toBeEmpty();
});

test('ignores only the edited occurrence while retaining other conflicting occurrences', function () {
    [$employee, , $service, $template] = schedulingOptionsFixture();
    $hour = AssignedHour::where('assigned_hours_template_id', $template->id)->firstOrFail();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $query = ['event_id' => $template->id, 'assigned_hour_id' => $hour->id];
    $this->getJson(employeeOptionsQuery($service, $query))->assertOk()->assertJsonPath('data.0.id', $employee->id);

    $other = new AssignedHour;
    $other->setRawAttributes(array_diff_key($hour->getRawOriginal(), array_flip(['id', 'created_at', 'updated_at'])));
    $other->save();
    $this->getJson(employeeOptionsQuery($service, $query))->assertOk()
        ->assertJsonPath('unavailable.0.conflicts.0.id', $other->id);
});

test('returns all distinct conflicts and excludes inactive workers', function () {
    [$employee, $client, $service] = schedulingOptionsFixture();
    AssignedHoursTemplate::factory()->create([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'recurrency' => 0, 'date' => '2026-11-03', 'date_start' => null, 'date_end' => null,
        'time_start' => '12:00', 'time_end' => '14:00', 'days_of_week' => null,
    ]);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $this->getJson(employeeOptionsQuery($service))->assertOk()->assertJsonCount(2, 'unavailable.0.conflicts');

    $employee->user->update(['is_active' => false]);
    $this->getJson(employeeOptionsQuery($service))->assertOk()->assertJsonCount(0, 'data')->assertJsonCount(0, 'unavailable');
});

test('returns 403 to non administrators requesting scheduling details', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::EMPLOYEE]));
    $this->getJson(route('api.employees.index'))->assertForbidden();
});
