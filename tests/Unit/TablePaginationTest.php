<?php

use App\Exports\BaseExport;
use App\Http\Middleware\SetupCheck;
use App\Models\Client;
use App\Models\User;
use App\Models\UserRole;
use Inertia\Testing\AssertableInertia as Assert;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->withoutMiddleware(SetupCheck::class);
});

dataset('paginated lists', [
    'clients' => ['clients.index', []],
    'employees' => ['employees.index', []],
    'users' => ['users.index', []],
    'budgets' => ['budgets.index', []],
    'invoices' => ['invoices.index', []],
    'contracts' => ['contracts.index', []],
    'budget templates' => ['budget-templates.index', []],
    'contract templates' => ['contract-templates.index', []],
    'characteristics' => ['characteristics.index', []],
    'announcements' => ['announcements.index', []],
    'audits' => ['audits.index', []],
    'time records by employee' => ['employee-time-records.index', ['group_by' => 'employee']],
    'time records by client' => ['employee-time-records.index', ['group_by' => 'client']],
]);

test('paginated lists use the requested page size', function (string $routeName, array $query) {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->get(route($routeName, [...$query, 'per_page' => 25]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('data.per_page', 25));
})->with('paginated lists');

test('paginated lists reject unsupported page sizes', function (string $routeName, array $query) {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->get(route($routeName, [...$query, 'per_page' => 101]))
        ->assertRedirect()
        ->assertSessionHasErrors('per_page');
})->with('paginated lists');

test('page sizes must be positive allowed integers', function (mixed $size) {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->get(route('users.index', ['per_page' => $size]))
        ->assertRedirect()
        ->assertSessionHasErrors('per_page');
})->with([0, -10, 1000, 'all', '25.5', [['25']]]);

test('lists accept each additional supported page size', function (int $size) {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->get(route('users.index', ['per_page' => $size]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('data.per_page', $size));
})->with([10, 15, 50, 100]);

test('lists retain their existing default page sizes', function (string $routeName, int $size) {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));

    $this->get(route($routeName))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('data.per_page', $size));
})->with([
    ['users.index', 10],
    ['audits.index', 50],
    ['employee-time-records.index', 15],
]);

test('users paginate filtered sorted results and preserve query parameters in links', function () {
    $admin = User::factory()->create(['role' => UserRole::ADMIN, 'name' => 'Administrator']);
    User::factory()->count(26)->sequence(fn ($sequence) => [
        'name' => sprintf('Pagination %02d', $sequence->index),
    ])->create(['role' => UserRole::ADMIN, 'is_active' => true]);
    User::factory()->create(['role' => UserRole::ADMIN, 'name' => 'Pagination inactive', 'is_active' => false]);
    $this->actingAs($admin);

    $response = $this->get(route('users.index', [
        'per_page' => 25,
        'filter_search' => 'Pagination',
        'filter_is_active' => '1',
        'sort' => 'name',
        'dir' => 'asc',
    ]));

    $response->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('data.data', 25)
        ->where('data.total', 26)
        ->where('data.data.0.name', 'Pagination 00')
        ->where('data.data.24.name', 'Pagination 24')
    );

    $nextUrl = $response->inertiaProps('data.next_page_url');
    parse_str(parse_url($nextUrl, PHP_URL_QUERY), $query);
    expect($query)->toMatchArray([
        'per_page' => '25',
        'filter_search' => 'Pagination',
        'filter_is_active' => '1',
        'sort' => 'name',
        'dir' => 'asc',
        'page' => '2',
    ]);

    $this->get($nextUrl)->assertOk()->assertInertia(fn (Assert $page) => $page
        ->has('data.data', 1)
        ->where('data.data.0.name', 'Pagination 25')
        ->where('data.current_page', 2)
    );
});

test('client related lists support page sizes', function (string $routeName, string $prop) {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $client = Client::factory()->make(['gender_id' => 1, 'country_id' => 'ES']);
    $client->save();

    $this->get(route($routeName, ['client' => $client->id, 'per_page' => 50]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where("{$prop}.per_page", 50));
})->with([
    ['clients.contracts', 'contracts'],
    ['clients.budgets', 'budgets'],
    ['clients.invoices', 'invoices'],
]);

test('client exports include all filtered records regardless of the page size', function () {
    $this->actingAs(User::factory()->create(['role' => UserRole::ADMIN]));
    $clients = Client::factory()->count(11)->make([
        'gender_id' => 1,
        'country_id' => 'ES',
        'name' => 'Pagination client',
    ]);
    $clients->each(fn (Client $client) => $client->save());
    $otherClient = Client::factory()->make(['gender_id' => 1, 'country_id' => 'ES', 'name' => 'Excluded']);
    $otherClient->save();
    Excel::fake();

    $this->get(route('clients.index', ['per_page' => 10, 'filter_search' => 'Pagination', 'export' => '1']))
        ->assertOk();

    Excel::assertDownloaded('clients.xlsx', fn (BaseExport $export): bool => $export->collection()->count() === 11);
});
