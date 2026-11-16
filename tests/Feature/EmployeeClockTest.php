<?php

use App\Http\Middleware\SetupCheck;
use App\Models\AssignedHour;
use App\Models\AssignedHoursTemplate;
use App\Models\Client;
use App\Models\Employee;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
    $this->travelTo(now()->setDate(2026, 10, 21)->setTime(9, 0));
});

function clockEmployee(string $mode = 'free'): Employee
{
    $user = User::factory()->create(['email' => 'employee@test.es', 'role' => UserRole::EMPLOYEE]);

    return Employee::create(Employee::factory()->raw([
        'user_id' => $user->id, 'gender_id' => 1, 'country_id' => 'es', 'time_record_mode' => $mode,
    ]));
}

test('administrative employees record multiple independent sessions without assigned work', function () {
    $employee = clockEmployee();
    $this->actingAs($employee->user);
    $this->post(route('employee.clock.store'), ['action' => 'in'])->assertSessionHasNoErrors()->assertRedirect();
    $record = $employee->timeRecords()->firstOrFail();
    expect($record->assigned_hour_id)->toBeNull();
    $this->get(route('employee.clock.show'))->assertJsonPath('time_record.id', $record->id);
    $this->post(route('employee.clock.store'), ['action' => 'in'])->assertSessionHasErrors('time_record');
    expect($employee->timeRecords()->count())->toBe(1);
    $this->travel(2)->hours();
    $this->post(route('employee.clock.store'), ['action' => 'out', 'time_record_id' => $record->id])->assertSessionHasNoErrors();
    expect($record->fresh()->registered_hours)->toBe('120');
    $this->post(route('employee.clock.store'), ['action' => 'out', 'time_record_id' => $record->id])->assertSessionHasErrors('time_record');
    $this->post(route('employee.clock.store'), ['action' => 'in'])->assertSessionHasNoErrors();
    expect($employee->timeRecords()->count())->toBe(2);
    $this->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page->component('EmployeeDashboard')->where('time_record_mode', 'free')->has('clock_records', 2));
    $this->get(route('employee.hours-worked'))->assertOk();
});

test('worked hours default to the current month', function () {
    $employee = clockEmployee();
    foreach (['2026-09-30', '2026-10-01', '2026-10-31', '2026-11-01'] as $date) {
        $employee->timeRecords()->create(['date_in' => $date, 'time_in' => '09:00:00']);
    }

    $this->actingAs($employee->user)->get(route('employee.hours-worked'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('filters.filter_month', '10')
        ->where('filters.filter_year', '2026')
        ->where('years', [2026])
        ->where('time_records.total', 2));
});

test('worked hours include the selected month boundaries and only the signed in employee records', function () {
    $employee = clockEmployee();
    $records = [];
    foreach (['2025-12-31', '2026-01-01', '2026-01-31', '2026-02-01'] as $date) {
        $records[] = $employee->timeRecords()->create(['date_in' => $date, 'time_in' => '09:00:00']);
    }
    $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
    $otherEmployee = Employee::create(Employee::factory()->raw([
        'user_id' => $otherUser->id, 'gender_id' => 1, 'country_id' => 'es',
    ]));
    $otherEmployee->timeRecords()->create(['date_in' => '2024-01-31', 'time_in' => '09:00:00']);
    $otherEmployee->timeRecords()->create(['date_in' => '2026-01-31', 'time_in' => '09:00:00']);

    $this->actingAs($employee->user)->get(route('employee.hours-worked', [
        'filter_month' => '1', 'filter_year' => '2026', 'sort' => 'date_in', 'dir' => 'asc',
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('filters.filter_month', '1')
        ->where('filters.filter_year', '2026')
        ->where('years', [2026, 2025])
        ->where('time_records.total', 2)
        ->where('time_records.data.0.id', $records[1]->id)
        ->where('time_records.data.1.id', $records[2]->id));
});

test('worked hours reject invalid months', function (string $month) {
    $employee = clockEmployee();

    $this->actingAs($employee->user)->get(route('employee.hours-worked', ['filter_month' => $month]))
        ->assertSessionHasErrors('filter_month');
})->with(['invalid', '0', '13', '2026-10']);

test('worked hours summarize all completed monthly records per client independently of pagination', function () {
    $employee = clockEmployee();
    $client = Client::create(Client::factory()->raw(['name' => 'Client A', 'gender_id' => 1, 'country_id' => 'es']));
    $secondClient = Client::create(Client::factory()->raw(['name' => 'Client B', 'gender_id' => 1, 'country_id' => 'es']));
    $service = Service::create(['name' => 'Test', 'icon_slug' => 'test', 'color' => 1, 'price' => 10, 'discount_partner' => 0]);
    $hours = [];
    foreach ([$client, $client, $secondClient] as $assignedClient) {
        $template = AssignedHoursTemplate::withoutEvents(fn () => AssignedHoursTemplate::factory()->create([
            'employee_id' => $employee->id, 'client_id' => $assignedClient->id, 'service_id' => $service->id,
            'date' => '2026-10-21', 'recurrency' => 0,
        ]));
        $hours[] = AssignedHour::create([
            'employee_id' => $employee->id, 'client_id' => $assignedClient->id, 'service_id' => $service->id,
            'assigned_hours_template_id' => $template->id,
            'date' => '2026-10-21', 'time_start' => '09:00:00', 'time_end' => '10:00:00',
        ]);
    }
    for ($index = 0; $index < 15; $index++) {
        $employee->timeRecords()->create([
            'assigned_hour_id' => $hours[0]->id, 'date_in' => '2026-10-21', 'time_in' => '09:00:00',
            'date_out' => '2026-10-21', 'time_out' => '09:30:00',
        ]);
    }
    $employee->timeRecords()->create([
        'assigned_hour_id' => $hours[1]->id, 'date_in' => '2026-10-31', 'time_in' => '23:00:00',
        'date_out' => '2026-11-01', 'time_out' => '01:00:00',
    ]);
    $employee->timeRecords()->create([
        'assigned_hour_id' => $hours[2]->id, 'date_in' => '2026-10-21', 'time_in' => '09:00:00',
        'date_out' => '2026-10-21', 'time_out' => '09:45:00',
    ]);
    $employee->timeRecords()->create([
        'date_in' => '2026-10-21', 'time_in' => '09:00:00', 'date_out' => '2026-10-21', 'time_out' => '10:00:00',
    ]);
    $employee->timeRecords()->create(['assigned_hour_id' => $hours[0]->id, 'date_in' => '2026-10-21', 'time_in' => '09:00:00']);
    $employee->timeRecords()->create([
        'assigned_hour_id' => $hours[0]->id, 'date_in' => '2026-09-30', 'time_in' => '09:00:00',
        'date_out' => '2026-09-30', 'time_out' => '10:00:00',
    ]);
    $otherUser = User::factory()->create(['role' => UserRole::EMPLOYEE]);
    $otherEmployee = Employee::create(Employee::factory()->raw(['user_id' => $otherUser->id, 'gender_id' => 1, 'country_id' => 'es']));
    $otherEmployee->timeRecords()->create([
        'assigned_hour_id' => $hours[0]->id, 'date_in' => '2026-10-21', 'time_in' => '09:00:00',
        'date_out' => '2026-10-21', 'time_out' => '10:00:00',
    ]);

    $this->actingAs($employee->user)->get(route('employee.hours-worked', ['per_page' => 10, 'page' => 2]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('time_records.current_page', 2)
        ->where('client_summary', [
            ['client_id' => $client->id, 'client_name' => 'Client A', 'registered_minutes' => 570],
            ['client_id' => $secondClient->id, 'client_name' => 'Client B', 'registered_minutes' => 45],
            ['client_id' => null, 'client_name' => __('employee_time_records.without_client'), 'registered_minutes' => 60],
        ]));
});

test('worked hours offer every year back to the first record and allow empty months', function () {
    $employee = clockEmployee();
    $employee->timeRecords()->create(['date_in' => '2023-05-10', 'time_in' => '09:00:00']);

    $this->actingAs($employee->user)->get(route('employee.hours-worked', [
        'filter_month' => '2', 'filter_year' => '2024',
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('years', [2026, 2025, 2024, 2023])
        ->where('filters.filter_month', '2')
        ->where('filters.filter_year', '2024')
        ->where('time_records.total', 0));
});

test('worked hours without records offer the current year', function () {
    $employee = clockEmployee();

    $this->actingAs($employee->user)->get(route('employee.hours-worked'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('years', [2026])
        ->where('filters.filter_month', '10')
        ->where('filters.filter_year', '2026')
        ->where('time_records.total', 0));
});

test('worked hours reject years outside the available range', function (string $year) {
    $employee = clockEmployee();
    $employee->timeRecords()->create(['date_in' => '2024-01-01', 'time_in' => '09:00:00']);

    $this->actingAs($employee->user)->get(route('employee.hours-worked', ['filter_year' => $year]))
        ->assertSessionHasErrors('filter_year');
})->with(['invalid', '2023', '2027']);

test('admins clients and service employees cannot use the administrative clock', function () {
    $employee = clockEmployee('work');
    $this->actingAs($employee->user)->post(route('employee.clock.store'), ['action' => 'in'])->assertForbidden();
    foreach ([UserRole::ADMIN, UserRole::CLIENT] as $role) {
        $this->actingAs(User::factory()->create(['role' => $role]));
        $this->post(route('employee.clock.store'), ['action' => 'in'])->assertForbidden();
        $this->get(route('employee.clock.show'))->assertForbidden();
    }
    $this->assertDatabaseCount('employee_time_records', 0);
});

test('guests cannot clock in', function () {
    $this->post(route('employee.clock.store'), ['action' => 'in'])->assertRedirect(route('login'));
    $this->assertDatabaseCount('employee_time_records', 0);
});

test('required location is validated and stored for entry and exit', function () {
    $employee = clockEmployee();
    DB::table('configurations')->insert([
        'id' => (string) Str::uuid(), 'company_name' => 'Test', 'company_cif_nif' => 'Test',
        'company_email' => 'test@example.com', 'company_phone' => '600000000', 'company_address' => 'Test',
        'company_city' => 'Test', 'company_zip_code' => '08001', 'company_country_id' => 'ES',
        'company_image' => '', 'require_time_record_location' => true,
    ]);
    $this->actingAs($employee->user);
    $this->post(route('employee.clock.store'), ['action' => 'in'])->assertSessionHasErrors('location');
    $this->assertDatabaseCount('employee_time_records', 0);
    $location = ['latitude' => 41.38, 'longitude' => 2.17, 'accuracy_meters' => 12];
    $this->post(route('employee.clock.store'), ['action' => 'in', 'location' => $location])->assertSessionHasNoErrors();
    $record = $employee->timeRecords()->firstOrFail();
    $this->travel(1)->hours();
    $this->post(route('employee.clock.store'), ['action' => 'out', 'time_record_id' => $record->id, 'location' => $location])->assertSessionHasNoErrors();
    expect($record->locations()->pluck('event_type')->all())->toBe(['in', 'out']);
});

test('an employee cannot close a record using a different session identifier', function () {
    $employee = clockEmployee();
    $record = $employee->timeRecords()->create(['date_in' => '2026-10-21', 'time_in' => '09:00:00']);
    $this->actingAs($employee->user)->post(route('employee.clock.store'), ['action' => 'out', 'time_record_id' => (string) Str::uuid()])->assertSessionHasErrors('time_record');
    expect($record->fresh()->time_out)->toBeNull();
});

test('administrative sessions can span midnight', function () {
    $employee = clockEmployee();
    $this->actingAs($employee->user);
    $this->travelTo(now()->setTime(23, 0));
    $this->post(route('employee.clock.store'), ['action' => 'in'])->assertSessionHasNoErrors();
    $record = $employee->timeRecords()->firstOrFail();
    $this->travel(2)->hours();
    $this->get(route('employee.clock.show'))->assertJsonPath('time_record.id', $record->id);
    $this->post(route('employee.clock.store'), ['action' => 'out', 'time_record_id' => $record->id])->assertSessionHasNoErrors();
    expect($record->fresh()->registered_hours)->toBe('120');
});

test('administrative records appear in employee client calendar and export views', function () {
    $employee = clockEmployee();
    $employee->timeRecords()->create([
        'date_in' => '2026-10-21', 'time_in' => '09:00:00', 'date_out' => '2026-10-21', 'time_out' => '11:00:00',
    ]);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $this->get(route('employee-time-records.index'))->assertInertia(fn (Assert $page) => $page
        ->where('data.data.0.registered_hours', '02:00')
        ->where('data.data.0.grouped_details.0.registered_hours', '02:00')
        ->where('data.data.0.grouped_details.0.service_name', __('employee_time_records.administrative_day')));
    $this->get(route('employee-time-records.index', ['group_by' => 'client']))->assertInertia(fn (Assert $page) => $page->where('data.data.0.id', 'administrative')->where('data.data.0.registered_hours', '02:00'));
    $this->get(route('employee-time-records.index', ['view' => 'calendar', 'employee_id' => $employee->id, 'filter_date' => '2026-10-21']))->assertInertia(fn (Assert $page) => $page->where('calendar.records.0.is_administrative', true)->where('calendar.records.0.registered_hours', '120'));
    Excel::fake();
    $this->get(route('employee-time-records.index', ['export' => 1]))->assertOk();
    Excel::assertDownloaded('time_records.xlsx', function ($export) {
        $sheets = $export->sheets();
        expect($sheets[0]->collection()->last()['registered_hours'])->toBe('02:00');
        expect($sheets[1]->collection()->last()['registered_hours'])->toBe('02:00');
        expect($sheets[2]->collection()->first()['registered_hours'])->toBe('02:00');

        return true;
    });
});

test('admins correct administrative sessions with an audited reason while employees cannot', function () {
    $employee = clockEmployee();
    $record = $employee->timeRecords()->create(['date_in' => '2026-10-21', 'time_in' => '09:00:00']);
    $payload = ['date_in' => '2026-10-21', 'time_in' => '09:00', 'date_out' => '2026-10-21', 'time_out' => '10:00', 'modification_reason' => 'Forgot to clock out'];
    $this->actingAs($employee->user)->put(route('employee-time-records.update', $record), $payload)->assertForbidden();
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $this->put(route('employee-time-records.update', $record), [...$payload, 'modification_reason' => ''])->assertSessionHasErrors('modification_reason');
    $this->put(route('employee-time-records.update', $record), $payload)->assertSessionHasNoErrors();
    expect($record->fresh()->registered_hours)->toBe('60');
    $this->assertDatabaseHas('audits', ['auditable_id' => $record->id, 'event' => 'updated']);
});

test('mode changes preserve history and reject switching with an open session', function () {
    $employee = clockEmployee();
    $record = $employee->timeRecords()->create(['date_in' => '2026-10-21', 'time_in' => '09:00:00']);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $payload = [
        'name' => $employee->user->name, 'email' => $employee->user->email,
        'nif' => $employee->nif, 'gender_id' => '1', 'annual_vacation_days' => 30,
        'phone' => '600000000', 'address' => 'Test', 'city' => 'Test', 'zip_code' => '08001',
        'country_id' => 'es', 'is_active' => true, 'time_record_mode' => 'work',
    ];
    $this->put(route('employees.update', $employee), $payload)->assertSessionHasErrors('time_record_mode');
    expect($employee->fresh()->time_record_mode)->toBe('free');
    $record->update(['date_out' => '2026-10-21', 'time_out' => '10:00:00']);
    $this->put(route('employees.update', $employee), $payload)->assertSessionHasNoErrors();
    expect($employee->fresh()->time_record_mode)->toBe('work');
    expect($record->fresh()->registered_hours)->toBe('60');
});

test('closed sessions spanning midnight contribute only todays minutes on the dashboard', function () {
    $employee = clockEmployee();
    $employee->timeRecords()->create([
        'date_in' => '2026-10-20', 'time_in' => '23:00:00', 'date_out' => '2026-10-21', 'time_out' => '01:00:00',
    ]);
    $this->actingAs($employee->user)->get(route('dashboard'))->assertInertia(fn (Assert $page) => $page
        ->has('clock_records', 1)->where('clock_today_minutes', 60));
});

test('service employees retain their assigned work clock and administrative employees cannot use it', function () {
    $employee = clockEmployee('work');
    $client = Client::create(Client::factory()->raw(['gender_id' => 1, 'country_id' => 'es']));
    $service = Service::create(['name' => 'Test service', 'icon_slug' => 'test', 'color' => 'blue', 'description' => 'Test', 'price' => 10, 'discount_partner' => 0]);
    $template = AssignedHoursTemplate::withoutEvents(fn () => AssignedHoursTemplate::factory()->create([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'date' => '2026-10-21', 'recurrency' => 0,
    ]));
    $work = AssignedHour::create([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'assigned_hours_template_id' => $template->id, 'date' => '2026-10-21', 'time_start' => '09:00:00', 'time_end' => '11:00:00',
    ]);
    $this->actingAs($employee->user)->post(route('employee.time-record.store', $work))->assertSessionHasNoErrors();
    $record = $employee->timeRecords()->firstOrFail();
    expect($record->assigned_hour_id)->toBe($work->id);
    $this->travel(1)->hours();
    $this->post(route('employee.time-record.store', $work))->assertSessionHasNoErrors();
    expect($record->fresh()->registered_hours)->toBe('60');
    $employee->update(['time_record_mode' => 'free']);
    $this->actingAs($employee->user->fresh());
    $this->post(route('employee.time-record.store', $work))->assertForbidden();
});

function scheduledClockWork(Employee $employee, string $date): AssignedHour
{
    $client = Client::create(Client::factory()->raw(['gender_id' => 1, 'country_id' => 'es']));
    $service = Service::create(['name' => 'Test service', 'icon_slug' => 'test', 'color' => Service::getColors()->first()->id, 'description' => 'Test', 'price' => 10, 'discount_partner' => 0]);
    $template = AssignedHoursTemplate::withoutEvents(fn () => AssignedHoursTemplate::factory()->create([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'date' => $date, 'recurrency' => 0,
    ]));

    return AssignedHour::create([
        'employee_id' => $employee->id, 'client_id' => $client->id, 'service_id' => $service->id,
        'assigned_hours_template_id' => $template->id, 'date' => $date, 'time_start' => '09:00:00', 'time_end' => '11:00:00',
    ]);
}

test('employees can read clients on other dates but cannot start their work clocks', function (string $date) {
    $employee = clockEmployee('work');
    $work = scheduledClockWork($employee, $date);

    $this->actingAs($employee->user)->get(route('employee.work.show', $work))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->where('can_record_time', false)
        ->where('work.client.id', $work->client_id));
    $this->post(route('employee.time-record.store', $work))
        ->assertSessionHasErrors(['time_record' => __('employee_time_records.only_today')]);

    $this->assertDatabaseCount('employee_time_records', 0);
})->with(['2026-10-20', '2026-10-22', '2026-10-28']);

test('todays assigned work displays the clock', function () {
    $employee = clockEmployee('work');
    $work = scheduledClockWork($employee, '2026-10-21');

    $this->actingAs($employee->user)->get(route('employee.work.show', $work))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('can_record_time', true));
});

test('employees can finish an assigned work clock after midnight but cannot restart it', function () {
    $employee = clockEmployee('work');
    $work = scheduledClockWork($employee, '2026-10-20');
    $record = $employee->timeRecords()->create([
        'assigned_hour_id' => $work->id, 'date_in' => '2026-10-20', 'time_in' => '23:00:00',
    ]);

    $this->actingAs($employee->user)->get(route('employee.work.show', $work))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('can_record_time', true));
    $this->post(route('employee.time-record.store', $work))->assertSessionHasNoErrors();

    expect($record->fresh()->time_out)->toBe('09:00:00');
    $this->post(route('employee.time-record.store', $work))->assertSessionHasErrors('time_record');
    $this->assertDatabaseCount('employee_time_records', 1);
});

test('admin corrections cannot reopen a second session or set an exit before entry', function () {
    $employee = clockEmployee();
    $closed = $employee->timeRecords()->create([
        'date_in' => '2026-10-21', 'time_in' => '07:00:00', 'date_out' => '2026-10-21', 'time_out' => '08:00:00',
    ]);
    $employee->timeRecords()->create(['date_in' => '2026-10-21', 'time_in' => '09:00:00']);
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $payload = ['date_in' => '2026-10-21', 'time_in' => '07:00', 'date_out' => null, 'time_out' => null, 'modification_reason' => 'Review'];
    $this->put(route('employee-time-records.update', $closed), $payload)->assertSessionHasErrors('time_out');
    $this->put(route('employee-time-records.update', $closed), [...$payload, 'date_out' => '2026-10-21', 'time_out' => '06:00'])->assertSessionHasErrors('time_out');
    expect($closed->fresh()->registered_hours)->toBe('60');
});
