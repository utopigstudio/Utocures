<?php

use App\Http\Middleware\SetupCheck;
use App\Models\AdminNotification;
use App\Models\AssignedHour;
use App\Models\AssignedHoursTemplate;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeStatusPeriod;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
    $this->travelTo(now()->setDate(2026, 10, 28)->setTime(9, 0));
});

function performanceEmployee(string $email = 'employee@test.es'): Employee
{
    $user = User::factory()->create(['email' => $email, 'role' => UserRole::EMPLOYEE]);
    $employee = Employee::factory()->make(['user_id' => $user->id, 'gender_id' => 1, 'country_id' => 'es']);
    $employee->saveQuietly();

    return $employee;
}

test('notification previews hydrate only the visible items while preserving the unread total', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    foreach (range(1, 35) as $number) {
        (new AdminNotification)->forceFill([
            'type' => 'test', 'title' => "Notification {$number}", 'message' => 'Test',
            'read_at' => $number <= 12 ? null : now(),
            'created_at' => now()->subMinutes(35 - $number),
        ])->save();
    }
    $retrieved = 0;
    AdminNotification::retrieved(function () use (&$retrieved): void {
        $retrieved++;
    });

    $this->get(route('dashboard'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('adminNotifications.count', 12)
        ->has('adminNotifications.items', 10)
        ->where('adminNotifications.items.0.title', 'Notification 12')
        ->where('adminNotifications.readCount', 20)
        ->has('adminNotifications.readItems', 20)
        ->where('adminNotifications.readItems.0.title', 'Notification 35'));

    expect($retrieved)->toBe(30);
});

test('employee hours are filtered paginated and isolated from other employees', function () {
    $employee = performanceEmployee();
    foreach (range(1, 12) as $day) {
        $employee->timeRecords()->create(['date_in' => sprintf('2026-10-%02d', $day), 'time_in' => '09:00:00']);
    }
    $employee->timeRecords()->create(['date_in' => '2026-09-30', 'time_in' => '09:00:00']);
    $employee->timeRecords()->create(['date_in' => '2025-10-01', 'time_in' => '09:00:00']);
    $other = performanceEmployee('other@test.es');
    $other->timeRecords()->create(['date_in' => '2026-10-28', 'time_in' => '09:00:00']);
    $this->actingAs($employee->user);

    $response = $this->get(route('employee.hours-worked', ['per_page' => 10]));
    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('time_records.total', 12)
        ->has('time_records.data', 10)
        ->where('time_records.data.0.date_in', '12-10-2026')
        ->where('filters.filter_period', 'monthly'));
    $this->get($response->inertiaProps('time_records.next_page_url'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->has('time_records.data', 2));
    $this->get(route('employee.hours-worked', ['filter_date' => '2026-09-10', 'filter_period' => 'yearly']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('time_records.total', 13));
    $this->get(route('employee.hours-worked', ['filter_date' => '2026-09-10']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('time_records.total', 1));
});

test('employee hours reject invalid filters and sorting', function (array $query, string $field) {
    $employee = performanceEmployee();

    $this->actingAs($employee->user)->get(route('employee.hours-worked', $query))
        ->assertRedirect()->assertSessionHasErrors($field);
})->with([
    'invalid date' => [['filter_date' => '2026-02-30'], 'filter_date'],
    'invalid period' => [['filter_period' => 'all'], 'filter_period'],
    'unbounded page size' => [['per_page' => 1000], 'per_page'],
    'unknown sort' => [['sort' => 'password'], 'sort'],
    'invalid direction' => [['dir' => 'invalid'], 'dir'],
]);

test('employee calendar loads only the selected week and keeps status information', function () {
    $employee = performanceEmployee();
    $client = Client::factory()->make();
    $client->saveQuietly();
    $service = Service::factory()->create();
    $template = AssignedHoursTemplate::factory()->make([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'recurrency' => 0, 'date' => '2026-10-28', 'time_start' => '09:00:00', 'time_end' => '10:00:00',
    ]);
    $template->saveQuietly();
    foreach (['2026-10-25', '2026-10-26', '2026-11-01', '2026-11-02'] as $date) {
        AssignedHour::create([
            'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
            'assigned_hours_template_id' => $template->id,
            'date' => $date, 'time_start' => '09:00:00', 'time_end' => '10:00:00',
        ]);
    }
    $active = $employee->statusPeriods()->create([
        'type' => EmployeeStatusPeriod::TYPE_ABSENCE, 'start_at' => '2026-10-28 08:00:00', 'end_at' => '2026-10-28 10:00:00',
    ]);
    $next = $employee->statusPeriods()->create([
        'type' => EmployeeStatusPeriod::TYPE_PERMISSION, 'start_at' => '2026-11-02 08:00:00', 'end_at' => '2026-11-02 10:00:00',
    ]);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->get(route('employees.show', $employee))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('hours', 2)
        ->where('calendar_date', '2026-10-26')
        ->where('current_status.code', EmployeeStatusPeriod::TYPE_ABSENCE)
        ->where('active_status_period.id', $active->id)
        ->where('next_status_period.id', $next->id));
    $this->get(route('employees.show', [$employee, 'filter_date' => '2026-11-04']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('hours', 1)->where('hours.0.date', '02/11/2026')->where('calendar_date', '2026-11-02'));
});

test('refreshing employee files and notes does not query unrelated page data', function () {
    $employee = performanceEmployee();
    $admin = User::factory()->create(['role' => UserRole::ADMIN]);
    $note = $employee->notes()->create(['content' => 'Employee note', 'type' => 'general', 'user_id' => $admin->id]);
    $this->actingAs($admin);
    $version = $this->get(route('employees.show', $employee))->viewData('page')['version'];
    DB::enableQueryLog();

    $response = $this->withHeaders([
        'X-Inertia' => 'true', 'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'employees/View', 'X-Inertia-Partial-Data' => 'files',
    ])->get(route('employees.show', $employee));
    $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
    DB::disableQueryLog();

    $response->assertOk()->assertJsonPath('props.files', [])->assertJsonMissingPath('props.hours')->assertJsonMissingPath('props.status_periods');
    expect($queries)->not->toContain('"assigned_hours"', '"employee_status_periods"', '"serviceable_id"', '"notes"');

    $this->withHeader('X-Inertia-Partial-Data', 'notes')->get(route('employees.show', $employee))
        ->assertOk()->assertJsonPath('props.notes.0.id', $note->id)
        ->assertJsonPath('props.notes.0.user.name', $admin->name)
        ->assertJsonMissingPath('props.hours');
});

test('refreshing client files does not query schedules or profile relations', function () {
    $client = Client::factory()->make();
    $client->saveQuietly();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $version = $this->get(route('clients.show', $client))->viewData('page')['version'];
    DB::enableQueryLog();

    $response = $this->withHeaders([
        'X-Inertia' => 'true', 'X-Inertia-Version' => $version,
        'X-Inertia-Partial-Component' => 'clients/View', 'X-Inertia-Partial-Data' => 'files',
    ])->get(route('clients.show', $client));
    $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
    DB::disableQueryLog();

    $response->assertOk()->assertJsonPath('props.files', [])->assertJsonMissingPath('props.assigned_hours_templates');
    expect($queries)->not->toContain('"assigned_hours_templates"', '"serviceable_id"', '"characterizable_id"');
});

test('client time records append the administrative group on its own correctly sized page', function () {
    $employee = performanceEmployee();
    $employee->timeRecords()->create(['date_in' => '2026-10-28', 'time_in' => '09:00:00']);
    foreach (range(1, 10) as $number) {
        $client = Client::factory()->make(['name' => sprintf('Client %02d', $number)]);
        $client->saveQuietly();
    }
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $response = $this->get(route('employee-time-records.index', ['group_by' => 'client', 'per_page' => 10, 'sort' => 'name', 'dir' => 'asc']));
    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('data.data', 10)->where('data.total', 11)->where('data.per_page', 10)
        ->where('data.data.0.name', 'Client 01'));
    $this->get($response->inertiaProps('data.next_page_url'))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('data.data', 1)->where('data.data.0.id', 'administrative')->where('data.total', 11));
    $this->get(route('employee-time-records.index', ['group_by' => 'client', 'filter_search' => 'Client 01']))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('data.total', 1)->has('data.data', 1));
});
