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
    $this->travelTo(now()->setDate(2026, 11, 2)->setTime(13, 0));
});

function recurringScheduleForUpdate(array $overrides = []): AssignedHoursTemplate
{
    $user = User::factory()->create(['email' => 'employee@test.es', 'role' => UserRole::EMPLOYEE]);
    $employee = Employee::factory()->make(['user_id' => $user->id, 'gender_id' => 1, 'country_id' => 'ES']);
    $employee->saveQuietly();
    $client = Client::factory()->make(['gender_id' => 1, 'country_id' => 'ES']);
    $client->saveQuietly();
    $service = Service::factory()->make();
    $service->saveQuietly();
    $employee->services()->attach($service);
    $client->services()->attach($service);
    foreach (range(1, 5) as $day) {
        AvailableHour::factory()->for($employee, 'hourable')->create(['day_of_week' => $day]);
    }
    test()->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    return AssignedHoursTemplate::factory()->create(array_replace([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'recurrency' => 1, 'days_of_week' => ['1', '2', '3', '4', '5'], 'date' => null,
        'date_start' => '2026-11-02', 'recurrence_anchor_date' => $overrides['date_start'] ?? '2026-11-02',
        'date_end' => null, 'time_start' => '10:00', 'time_end' => '12:30',
    ], $overrides));
}

function recurringScheduleUpdateData(AssignedHoursTemplate $template, array $overrides = []): array
{
    return array_replace([
        'service_id' => $template->service_id, 'employee_id' => $template->employee_id,
        'recurrency' => $template->recurrency, 'days_of_week' => $template->days_of_week,
        'date' => null, 'date_start' => $template->date_start?->toDateString(),
        'date_end' => $template->date_end?->toDateString(), 'time_start' => '10:30', 'time_end' => '13:00',
    ], $overrides);
}

function clockedRecurringHour(AssignedHoursTemplate $template, string $date): AssignedHour
{
    $hour = $template->assignedHours()->whereDate('date', $date)->firstOrFail();
    $hour->timeRecords()->create([
        'employee_id' => $template->employee_id, 'date_in' => $date, 'date_out' => $date,
        'time_in' => '10:00:00', 'time_out' => '12:30:00',
    ]);

    return $hour;
}

test('allows a later recurrence change while preserving todays clocked service', function (string $originalStart) {
    $template = recurringScheduleForUpdate(['date_start' => $originalStart]);
    $hour = clockedRecurringHour($template, '2026-11-16');
    $record = $hour->timeRecords()->firstOrFail();
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date_start' => '2026-11-18']))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('assigned_hours', ['id' => $hour->id, 'time_start' => '10:00']);
    $this->assertModelExists($record);
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'date_end' => '2026-11-17']);
    $this->assertDatabaseHas('assigned_hours', [
        'assigned_hours_template_id' => $template->id, 'date' => '2026-11-17', 'time_start' => '10:00',
    ]);
    $newTemplate = AssignedHoursTemplate::whereKeyNot($template->id)->sole();
    $this->assertDatabaseHas('assigned_hours', [
        'assigned_hours_template_id' => $newTemplate->id, 'date' => '2026-11-18',
        'time_start' => '10:30', 'time_end' => '13:00',
    ]);
})->with(['already started' => '2026-11-02', 'starts today' => '2026-11-16']);

test('preserves intermediate services and daily exceptions before the chosen start', function () {
    $template = recurringScheduleForUpdate();
    $exception = $template->assignedHours()->whereDate('date', '2026-11-17')->firstOrFail();
    $exception->update(['time_start' => '09:00', 'time_end' => '11:30']);
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date_start' => '2026-11-20']))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('assigned_hours', ['id' => $exception->id, 'time_start' => '09:00']);
    foreach (['2026-11-16', '2026-11-18', '2026-11-19'] as $date) {
        $this->assertDatabaseHas('assigned_hours', [
            'assigned_hours_template_id' => $template->id, 'date' => $date, 'time_start' => '10:00',
        ]);
    }
    $this->assertDatabaseMissing('assigned_hours', ['assigned_hours_template_id' => $template->id, 'date' => '2026-11-20']);
    $this->assertDatabaseHas('assigned_hours', ['date' => '2026-11-20', 'time_start' => '10:30']);
});

test('rejects changes affecting a clocked service and rolls back the complete schedule', function (string $start, string $clockedDate) {
    $template = recurringScheduleForUpdate();
    $hour = clockedRecurringHour($template, $clockedDate);
    $originalCount = AssignedHour::count();
    $generatedUntil = $template->generated_until->toDateString();
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date_start' => $start]))
        ->assertRedirect()->assertSessionHasErrors(['date_start' => __('employees.assigned_hour_locked')]);

    $this->assertDatabaseHas('assigned_hours_templates', [
        'id' => $template->id, 'date_start' => '2026-11-02', 'date_end' => null,
        'time_start' => '10:00', 'generated_until' => $generatedUntil,
    ]);
    $this->assertDatabaseCount('assigned_hours_templates', 1);
    $this->assertDatabaseCount('assigned_hours', $originalCount);
    $this->assertModelExists($hour);
    $this->assertDatabaseHas('employee_time_records', ['assigned_hour_id' => $hour->id]);
})->with(['from today' => ['2026-11-02', '2026-11-16'], 'on the chosen start' => ['2026-11-18', '2026-11-18']]);

test('changes the recurrence from today without modifying past clocked services', function () {
    $template = recurringScheduleForUpdate();
    $hour = clockedRecurringHour($template, '2026-11-13');
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template))->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('assigned_hours', ['id' => $hour->id, 'time_start' => '10:00']);
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'date_end' => '2026-11-15']);
    $this->assertDatabaseHas('assigned_hours', ['date' => '2026-11-16', 'time_start' => '10:30']);
});

test('reschedules a recurrence that has not started without retaining its old dates', function () {
    $template = recurringScheduleForUpdate(['date_start' => '2026-11-18']);
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date_start' => '2026-11-20']))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assigned_hours_templates', 1);
    $this->assertDatabaseMissing('assigned_hours', ['date' => '2026-11-18']);
    $this->assertDatabaseMissing('assigned_hours', ['date' => '2026-11-19']);
    $this->assertDatabaseHas('assigned_hours', [
        'assigned_hours_template_id' => $template->id, 'date' => '2026-11-20', 'time_start' => '10:30',
    ]);
});

test('changes only the end date without regenerating todays clocked service', function () {
    $template = recurringScheduleForUpdate(['date_start' => '2026-11-16']);
    $hour = clockedRecurringHour($template, '2026-11-16');
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['time_start' => '10:00', 'time_end' => '12:30', 'date_end' => '2026-11-18']))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assigned_hours_templates', 1);
    $this->assertModelExists($hour);
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'date_end' => '2026-11-18']);
    $this->assertDatabaseHas('assigned_hours', ['assigned_hours_template_id' => $template->id, 'date' => '2026-11-18']);
    $this->assertDatabaseMissing('assigned_hours', ['assigned_hours_template_id' => $template->id, 'date' => '2026-11-19']);
});

test('does not skip ungenerated services when setting an end beyond the current horizon', function () {
    $template = recurringScheduleForUpdate();
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['time_start' => '10:00', 'time_end' => '12:30', 'date_end' => '2027-03-01']))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('assigned_hours', ['assigned_hours_template_id' => $template->id, 'date' => '2027-02-01']);
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'generated_until' => '2027-02-14']);
});

test('continues generating the old schedule until a change beyond the current horizon', function () {
    $template = recurringScheduleForUpdate();
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date_start' => '2027-03-01']))
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->travelTo(now()->setDate(2027, 1, 4));
    $this->artisan('assigned-hours:generate')->assertExitCode(0);

    $this->assertDatabaseHas('assigned_hours', [
        'assigned_hours_template_id' => $template->id, 'date' => '2027-02-01', 'time_start' => '10:00',
    ]);
    $this->assertDatabaseHas('assigned_hours', ['date' => '2027-03-01', 'time_start' => '10:30']);
    $this->assertDatabaseMissing('assigned_hours', ['assigned_hours_template_id' => $template->id, 'date' => '2027-03-01']);
});

test('preserves alternate weeks when changing the time of an ongoing biweekly schedule', function () {
    $template = recurringScheduleForUpdate(['recurrency' => 2]);
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template))->assertRedirect()->assertSessionHasNoErrors();

    $newTemplate = AssignedHoursTemplate::whereKeyNot($template->id)->sole();
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $newTemplate->id, 'recurrence_anchor_date' => '2026-11-02']);
    foreach (['2026-11-16', '2026-11-30'] as $date) {
        $this->assertDatabaseHas('assigned_hours', [
            'assigned_hours_template_id' => $newTemplate->id, 'date' => $date, 'time_start' => '10:30',
        ]);
    }
    $this->assertDatabaseMissing('assigned_hours', ['assigned_hours_template_id' => $newTemplate->id, 'date' => '2026-11-23']);
});

test('rejects an end date that would remove a clocked service', function () {
    $template = recurringScheduleForUpdate(['date_start' => '2026-11-16']);
    $hour = clockedRecurringHour($template, '2026-11-18');
    $originalCount = AssignedHour::count();
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['time_start' => '10:00', 'time_end' => '12:30', 'date_end' => '2026-11-17']))
        ->assertRedirect()->assertSessionHasErrors(['date_start' => __('employees.assigned_hour_locked')]);

    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'date_end' => null]);
    $this->assertDatabaseCount('assigned_hours', $originalCount);
    $this->assertModelExists($hour);
    $this->assertDatabaseHas('employee_time_records', ['assigned_hour_id' => $hour->id]);
});

test('keeps the original end date when the replacement begins after it', function () {
    $template = recurringScheduleForUpdate(['date_end' => '2026-11-18']);
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date_start' => '2026-11-23', 'date_end' => null]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'date_end' => '2026-11-18']);
    $this->assertDatabaseMissing('assigned_hours', ['date' => '2026-11-19']);
    $this->assertDatabaseHas('assigned_hours', ['date' => '2026-11-23', 'time_start' => '10:30']);
});

test('uses the selected date when replacing an ongoing recurrence with a single service', function () {
    $template = recurringScheduleForUpdate();
    $hour = clockedRecurringHour($template, '2026-11-16');
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['recurrency' => 0, 'days_of_week' => [], 'date' => '2026-11-18']))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertModelExists($hour);
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'date_end' => '2026-11-17']);
    $this->assertDatabaseHas('assigned_hours', [
        'assigned_hours_template_id' => $template->id, 'date' => '2026-11-17', 'time_start' => '10:00',
    ]);
    $newTemplate = AssignedHoursTemplate::whereKeyNot($template->id)->sole();
    $newHour = $newTemplate->assignedHours()->sole();
    expect($newHour->date->toDateString())->toBe('2026-11-18');
    expect($newHour->time_start->format('H:i'))->toBe('10:30');
});

test('moves an unclocked single service to a later date without retaining the original service', function () {
    $template = recurringScheduleForUpdate([
        'recurrency' => 0, 'date' => '2026-11-16', 'date_start' => null,
        'recurrence_anchor_date' => null, 'days_of_week' => null,
    ]);
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('clients.assigned-hours-templates.update', [$template->client_id, $template->id]),
        recurringScheduleUpdateData($template, ['date' => '2026-11-18', 'days_of_week' => []]))
        ->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assigned_hours_templates', 1);
    $this->assertDatabaseCount('assigned_hours', 1);
    $hour = $template->assignedHours()->sole();
    expect($hour->date->toDateString())->toBe('2026-11-18');
    expect($hour->time_start->format('H:i'))->toBe('10:30');
});

test('daily edits still affect only the selected service when another day is clocked', function () {
    $template = recurringScheduleForUpdate();
    clockedRecurringHour($template, '2026-11-16');
    $hour = $template->assignedHours()->whereDate('date', '2026-11-18')->firstOrFail();
    $this->travelTo(now()->setDate(2026, 11, 16));

    $this->put(route('employees.assigned-hour-events.update', [$template->employee_id, $hour->id]), [
        'employee_id' => $template->employee_id, 'date' => '2026-11-18', 'time_start' => '10:30', 'time_end' => '13:00',
    ])->assertRedirect()->assertSessionHasNoErrors();

    $this->assertDatabaseCount('assigned_hours_templates', 1);
    expect($hour->fresh()->time_start->format('H:i'))->toBe('10:30');
    $this->assertDatabaseHas('assigned_hours_templates', ['id' => $template->id, 'time_start' => '10:00']);
    $this->assertDatabaseHas('assigned_hours', [
        'assigned_hours_template_id' => $template->id, 'date' => '2026-11-19', 'time_start' => '10:00',
    ]);
});
