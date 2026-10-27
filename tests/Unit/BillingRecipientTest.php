<?php

use App\Exports\BaseExport;
use App\Http\Middleware\SetupCheck;
use App\Models\AssignedHour;
use App\Models\AssignedHoursTemplate;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeTimeRecord;
use App\Models\Invoice;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
});

function billingTestAdmin(): void
{
    test()->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
}

function billingTestRecipient(): array
{
    return ['name' => 'Familia Test SL', 'cif_nif' => 'B12345678', 'address' => 'Calle Fiscal 10', 'city' => 'Barcelona', 'zip_code' => '08001', 'country_id' => 'es', 'email' => 'facturas@example.com'];
}

function billingTestClient(?array $recipient = null): Client
{
    $client = Client::factory()->make(['gender_id' => 1, 'country_id' => 'es', 'billing_recipient' => $recipient, 'automatic_invoice' => false]);
    $client->save();

    return $client;
}

function billingTestClientPayload(Client $client, array $overrides = []): array
{
    return array_replace($client->only(['name', 'cif_nif', 'email', 'phone', 'address', 'city', 'zip_code', 'country_id', 'tax_included', 'is_partner', 'automatic_invoice']), ['gender_id' => '1'], $overrides);
}

function billingTestInvoicePayload(Client $client): array
{
    return ['client_id' => $client->id, 'date' => '2026-10-15', 'due_date' => '2026-11-15', 'lines' => [['concept' => 'Servicio de asistencia', 'quantity' => 1, 'price' => 50, 'discount' => 0, 'tax_type' => '21']]];
}

test('a client can store a separate billing recipient without changing service recipient data', function () {
    billingTestAdmin();
    $client = billingTestClient();
    $name = $client->name;

    $this->put(route('clients.update', $client), billingTestClientPayload($client, ['use_billing_recipient' => '1', 'billing_recipient' => billingTestRecipient()]))
        ->assertRedirect(route('clients.index'))->assertSessionHasNoErrors();

    expect($client->fresh()->billing_recipient)->toBe(billingTestRecipient());
    expect($client->fresh()->name)->toBe($name);
});

test('new clients can be created with a separate billing recipient', function () {
    billingTestAdmin();
    $client = Client::factory()->make(['gender_id' => 1, 'country_id' => 'es']);

    $this->post(route('clients.store'), billingTestClientPayload($client, ['use_billing_recipient' => '1', 'billing_recipient' => billingTestRecipient()]))
        ->assertRedirect(route('clients.index'))->assertSessionHasNoErrors();

    expect(Client::where('email', $client->email)->firstOrFail()->billing_recipient)->toBe(billingTestRecipient());
});

test('separate billing details are validated before saving', function (array $recipient, string $error) {
    billingTestAdmin();
    $client = billingTestClient();

    $this->put(route('clients.update', $client), billingTestClientPayload($client, ['use_billing_recipient' => '1', 'billing_recipient' => $recipient]))
        ->assertRedirect()->assertSessionHasErrors($error);

    expect($client->fresh()->billing_recipient)->toBeNull();
})->with([
    'missing name' => [array_replace(billingTestRecipient(), ['name' => '']), 'billing_recipient.name'],
    'missing tax id' => [array_replace(billingTestRecipient(), ['cif_nif' => '']), 'billing_recipient.cif_nif'],
    'missing address' => [array_replace(billingTestRecipient(), ['address' => '']), 'billing_recipient.address'],
    'missing city' => [array_replace(billingTestRecipient(), ['city' => '']), 'billing_recipient.city'],
    'invalid postcode' => [array_replace(billingTestRecipient(), ['zip_code' => 'abc']), 'billing_recipient.zip_code'],
    'unsupported country' => [array_replace(billingTestRecipient(), ['country_id' => 'invalid']), 'billing_recipient.country_id'],
    'invalid email' => [array_replace(billingTestRecipient(), ['email' => 'invalid']), 'billing_recipient.email'],
    'unexpected fields' => [[...billingTestRecipient(), 'bank_account' => 'unexpected'], 'billing_recipient'],
]);

test('disabling a separate recipient clears saved details and ignores hidden input', function () {
    billingTestAdmin();
    $client = billingTestClient(billingTestRecipient());

    $this->put(route('clients.update', $client), billingTestClientPayload($client, ['use_billing_recipient' => '0', 'billing_recipient' => ['email' => 'invalid']]))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($client->fresh()->billing_recipient)->toBeNull();
});

test('billing email is optional and updates without the new control preserve existing settings', function () {
    billingTestAdmin();
    $recipient = billingTestRecipient();
    unset($recipient['email']);
    $client = billingTestClient();

    $this->put(route('clients.update', $client), billingTestClientPayload($client, ['use_billing_recipient' => '1', 'billing_recipient' => $recipient]))
        ->assertRedirect()->assertSessionHasNoErrors();
    $this->put(route('clients.update', $client), billingTestClientPayload($client))
        ->assertRedirect()->assertSessionHasNoErrors();

    expect($client->fresh()->billing_recipient)->toBe($recipient);
});

test('manual invoices copy the recipient and ignore submitted recipient overrides', function () {
    billingTestAdmin();
    $client = billingTestClient(billingTestRecipient());

    $this->post(route('invoices.store'), [...billingTestInvoicePayload($client), 'billing_recipient' => ['name' => 'Forged']])
        ->assertRedirect(route('invoices.index'))->assertSessionHasNoErrors();

    $invoice = Invoice::firstOrFail();
    expect($invoice->billing_recipient)->toBe(billingTestRecipient());
    expect($invoice->address)->toBe('Calle Fiscal 10');
    expect($invoice->lines()->count())->toBe(1);
    expect((float) $invoice->total)->toBe(60.5);
});

test('invoices use the client data when there is no separate recipient', function () {
    billingTestAdmin();
    $client = billingTestClient();

    $this->post(route('invoices.store'), billingTestInvoicePayload($client))->assertRedirect()->assertSessionHasNoErrors();

    expect(Invoice::firstOrFail()->billing_recipient)->toBe($client->billingRecipientData());
});

test('editing an invoice preserves its recipient after the client billing settings change', function () {
    billingTestAdmin();
    $client = billingTestClient(billingTestRecipient());
    $this->post(route('invoices.store'), billingTestInvoicePayload($client))->assertRedirect();
    $invoice = Invoice::firstOrFail();
    $client->update(['billing_recipient' => null, 'name' => 'Changed client']);

    $this->put(route('invoices.update', $invoice), billingTestInvoicePayload($client))->assertRedirect()->assertSessionHasNoErrors();
    $this->get(route('invoices.edit', $invoice))->assertOk()->assertInertia(fn (Assert $page) => $page->where('billing_recipient.name', 'Familia Test SL'));

    expect($invoice->fresh()->billing_recipient)->toBe(billingTestRecipient());
    expect($invoice->fresh()->address)->toBe('Calle Fiscal 10');
});

test('changing an invoice client uses the new client recipient even with a cached relation', function () {
    billingTestAdmin();
    $client = billingTestClient(billingTestRecipient());
    $otherClient = billingTestClient();
    $this->post(route('invoices.store'), billingTestInvoicePayload($client))->assertRedirect();
    $invoice = Invoice::firstOrFail();
    $invoice->load('client');
    $invoice->update(['client_id' => $otherClient->id]);

    expect($invoice->fresh()->billing_recipient)->toBe($otherClient->billingRecipientData());
});

test('invoice creation previews the configured recipient', function () {
    billingTestAdmin();
    $client = billingTestClient(billingTestRecipient());

    $this->get(route('invoices.create', ['recipient_client_id' => $client->id]))
        ->assertOk()->assertInertia(fn (Assert $page) => $page->where('billing_recipient.name', 'Familia Test SL'));
});

test('invoice searches and exports include the saved recipient', function () {
    billingTestAdmin();
    $this->travelTo(now()->setDate(2026, 10, 27));
    $client = billingTestClient(billingTestRecipient());
    $this->post(route('invoices.store'), billingTestInvoicePayload($client))->assertRedirect();

    $this->get(route('invoices.index', ['filter_search' => 'B12345678']))->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('data.total', 1)->where('data.data.0.billing_recipient.name', 'Familia Test SL'));

    Excel::fake();
    $this->get(route('invoices.index', ['filter_search' => 'B12345678', 'export' => 1]))->assertOk();
    Excel::assertDownloaded('invoices.xlsx', function (BaseExport $export): bool {
        $row = $export->collection()->first();

        return $row['billing_recipient.name'] === 'Familia Test SL'
            && $row['billing_recipient.zip_code'] === '08001'
            && in_array('Destinatario de facturación', $export->headings(), true);
    });
});

test('automatic invoices store the configured billing recipient', function () {
    $this->travelTo(now()->setDate(2026, 10, 27));
    $client = billingTestClient(billingTestRecipient());
    $client->update(['automatic_invoice' => true]);
    $employeeUser = User::factory()->create(['email' => 'employee@test.es', 'role' => UserRole::EMPLOYEE]);
    $employee = Employee::factory()->make(['user_id' => $employeeUser->id, 'gender_id' => 1, 'country_id' => 'es']);
    $employee->save();
    $service = Service::factory()->make(['price' => 20, 'discount_partner' => 0]);
    $service->save();
    $template = AssignedHoursTemplate::factory()->make(['client_id' => $client->id, 'employee_id' => $employee->id, 'service_id' => $service->id, 'recurrency' => 0, 'date' => '2026-10-15', 'days_of_week' => [], 'date_start' => null, 'date_end' => null]);
    $template->saveQuietly();
    $assignedHour = AssignedHour::create(['assigned_hours_template_id' => $template->id, 'client_id' => $client->id, 'employee_id' => $employee->id, 'service_id' => $service->id, 'date' => '2026-10-15', 'time_start' => '09:00', 'time_end' => '10:00']);
    EmployeeTimeRecord::create(['employee_id' => $employee->id, 'assigned_hour_id' => $assignedHour->id, 'date_in' => '2026-10-15', 'date_out' => '2026-10-15', 'time_in' => '09:00', 'time_out' => '10:00']);

    $this->artisan('invoices:generate-automatic')->assertSuccessful();

    expect(Invoice::where('client_id', $client->id)->firstOrFail()->billing_recipient)->toBe(billingTestRecipient());
});

test('the migration preserves legacy invoice addresses when freezing available recipient data', function () {
    $client = billingTestClient();
    $migration = require database_path('migrations/2026_10_27_110434_add_billing_recipients_to_clients_and_invoices.php');
    $migration->down();
    $id = (string) Str::uuid();
    DB::table('invoices')->insert(['id' => $id, 'client_id' => $client->id, 'date' => '2026-01-01', 'address' => 'Historical address', 'city' => 'Historical city', 'zip_code' => 8001, 'country_id' => 'es']);

    $migration->up();

    $recipient = Invoice::findOrFail($id)->billing_recipient;
    expect($recipient['name'])->toBe($client->name);
    expect($recipient['address'])->toBe('Historical address');
    expect($recipient['city'])->toBe('Historical city');
});
