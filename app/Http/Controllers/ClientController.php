<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Http\Requests\AssignedHourTemplate\AssignedHoursTemplateStoreRequest;
use App\Http\Requests\AssignedHourTemplate\AssignedHoursTemplateUpdateRequest;
use App\Http\Requests\Budget\BudgetIndexRequest;
use App\Http\Requests\Client\ClientIndexRequest;
use App\Http\Requests\Client\ClientStoreRequest;
use App\Http\Requests\Contract\ContractIndexRequest;
use App\Http\Requests\Invoice\InvoiceIndexRequest;
use App\Http\Requests\Note\NoteIndexRequest;
use App\Models\Budget;
use App\Models\Characteristic;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Country;
use App\Models\Gender;
use App\Models\Note;
use App\Models\Service;
use App\Services\AssignedHoursSchedulingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ClientController extends Controller
{
    private const LIST_FIELDS = ['id', 'cif_nif', 'name', 'email', 'phone', 'is_partner', 'address', 'city', 'zip_code', 'created_at'];

    private const EXPORT_FIELDS = ['cif_nif', 'name', 'email', 'phone', 'is_partner', 'address', 'city', 'zip_code', 'created_at'];

    private const SEARCHABLE = ['name', 'email', 'cif_nif'];

    private const LIST_CONTRACTS_FIELDS = ['id', 'status', 'title', 'date_start', 'date_end'];

    private const LIST_INVOICES_FIELDS = ['id', 'client_id', 'billing_recipient', 'created_at', 'subtotal', 'discount', 'total'];

    private const LIST_BUDGETS_FIELDS = ['id', 'client_name', 'status', 'created_at', 'subtotal', 'discount', 'total'];

    private const RELATION_FIELDS = ['services', 'characteristics'];

    public function index(ClientIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_is_partner' => $request->validated('filter_is_partner'),
        ];
        $export = $request->validated('export') ?? false;

        $clients = Client::select(self::LIST_FIELDS)
            ->filter($filters, self::SEARCHABLE)
            ->orderBy($sort, $dir);

        if ($export) {
            $clients = $clients->get();

            return Excel::download(new BaseExport($clients, 'clients', self::EXPORT_FIELDS), 'clients.xlsx');
        }

        $clients = $clients->paginate((int) ($request->validated('per_page') ?? 10))
            ->withQueryString();

        return Inertia::render('clients/List', [
            'data' => $clients,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function show(Client $client): Response
    {
        return Inertia::render('clients/View', [
            'client' => fn () => $client->load([
                'services',
                'assignedCharacteristics.characteristic',
                'user:id,name,email,is_active,invitation_accepted_at,created_at',
            ]),
            'services' => fn () => Service::select('id', 'name')->get(),
            'files' => fn () => $client->files()->get(),
            'assigned_hours_templates' => fn () => $client->assignedHoursTemplates()->with([
                'service',
                'employee:id,phone,user_id',
                'employee.user:id,name,email,avatar',
            ])->get(),
        ]);
    }

    public function contracts(ContractIndexRequest $request, Client $client)
    {
        $statuses = Contract::getStatuses();
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['client.name', 'client.cif_nif'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_status' => $request->validated('filter_status'),
            'filter_year' => $request->validated('filter_year') ?? date('Y'),
        ];

        $contracts = $client->contracts()
            ->select(self::LIST_CONTRACTS_FIELDS)
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate((int) ($request->validated('per_page') ?? 10))
            ->withQueryString();

        return Inertia::render('clients/Contracts', [
            'client' => $client,
            'contracts' => $contracts,
            'statuses' => $statuses,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function invoices(InvoiceIndexRequest $request, Client $client)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['date'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];

        $invoices = $client->invoices()
            ->select(self::LIST_INVOICES_FIELDS)
            ->with('client:id,name')
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate((int) ($request->validated('per_page') ?? 10))
            ->withQueryString();

        return Inertia::render('clients/Invoices', [
            'client' => $client,
            'invoices' => $invoices,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function budgets(BudgetIndexRequest $request, Client $client)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['client_name'];
        $filters = [
            'filter_status' => $request->validated('filter_status'),
        ];

        $budgets = $client->budgets()
            ->select(self::LIST_BUDGETS_FIELDS)
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate((int) ($request->validated('per_page') ?? 10))
            ->withQueryString();

        return Inertia::render('clients/Budgets', [
            'client' => $client,
            'budgets' => $budgets,
            'statuses' => Budget::getStatuses(),
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function notes(NoteIndexRequest $request, Client $client)
    {
        $searchable = ['content', 'user.name'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'highlight_note' => $request->validated('highlight_note'),
        ];

        $notes = $client->notes()
            ->with([
                'user:id,name,avatar',
                'employeeTimeRecord' => function ($query) {
                    $query->with([
                        'employee.user:id,name,avatar',
                        'assignedHour.service:id,name',
                    ]);
                },
            ])
            ->filter($filters, $searchable)
            ->orderByDesc('created_at')
            ->get();

        return Inertia::render('clients/Notes', [
            'client' => $client,
            'notes' => $notes,
            'highlightNoteId' => $request->validated('highlight_note'),
            'incidentType' => Note::TYPE_INCIDENT,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $countries = Country::all();
        $genders = Gender::all();
        $services = Service::select('id', 'name')->get();
        $characteristics = Characteristic::with('options')->get();

        return Inertia::render('clients/Form', [
            'countries' => $countries,
            'genders' => $genders,
            'services' => $services,
            'characteristics' => $characteristics,
        ]);
    }

    public function edit(Client $client)
    {
        $countries = Country::all();
        $genders = Gender::all();
        $services = Service::select('id', 'name')->get();
        $characteristics = Characteristic::with('options')->get();

        $client->load('services', 'assignedCharacteristics.characteristic');

        return Inertia::render('clients/Form', [
            'client' => $client,
            'countries' => $countries,
            'genders' => $genders,
            'services' => $services,
            'characteristics' => $characteristics,
        ]);
    }

    public function store(ClientStoreRequest $request)
    {
        $client = DB::transaction(function () use ($request) {
            $data = $request->safe()->except([...self::RELATION_FIELDS, 'use_billing_recipient']);
            $data['billing_recipient'] = $request->boolean('use_billing_recipient') ? $request->validated('billing_recipient') : null;
            $client = Client::create($data);

            if ($request->filled('services')) {
                $client->auditSync('services', $request->validated('services'));
            }

            if ($request->filled('characteristics')) {
                $client->auditSync('assignedCharacteristics', $request->validated('characteristics'));
            }

            return $client;
        });

        return to_route('clients.index')->with('success', "Cliente {$client->name} creado correctamente.");
    }

    public function update(ClientStoreRequest $request, Client $client)
    {
        DB::transaction(function () use ($client, $request) {
            $data = $request->safe()->except([...self::RELATION_FIELDS, 'use_billing_recipient']);
            if ($request->has('use_billing_recipient')) {
                $data['billing_recipient'] = $request->boolean('use_billing_recipient') ? $request->validated('billing_recipient') : null;
            }
            $client->update($data);

            if ($request->filled('services')) {
                $client->auditSync('services', $request->validated('services'));
            } else {
                $client->auditDetach('services');
            }

            if ($request->filled('characteristics')) {
                $client->auditSync('assignedCharacteristics', $request->validated('characteristics'));
            } else {
                $client->auditDetach('assignedCharacteristics');
            }
        });

        return to_route('clients.index')->with('success', "Cliente {$client->name} actualizado correctamente.");
    }

    public function destroy(Client $client)
    {
        DB::transaction(function () use ($client) {
            if ($client->user) {
                $client->user->deactivate();
            }

            $client->delete();
        });

        return to_route('clients.index')->with('success', "Cliente {$client->name} eliminado correctamente.");
    }

    public function storeAssignedHourTemplate(
        AssignedHoursTemplateStoreRequest $request,
        Client $client,
        AssignedHoursSchedulingService $schedulingService,
    ) {
        $data = $schedulingService->normalizeTemplateData($request->validated());
        $schedulingService->ensureTemplateCanBeAssigned($client, $data);

        DB::transaction(fn () => $client->assignedHoursTemplates()->create($data));

        return back()->with('success', 'Plantilla de horario creada correctamente');
    }

    public function updateAssignedHourTemplate(
        AssignedHoursTemplateUpdateRequest $request,
        Client $client,
        string $template,
        AssignedHoursSchedulingService $schedulingService,
    ) {
        $hourTemplate = $client->assignedHoursTemplates()->findOrFail($template);
        $hourTemplate->scheduleOriginalAttributes = $hourTemplate->getRawOriginal();
        $data = $schedulingService->normalizeTemplateData($request->validated(), $hourTemplate);
        $schedulingService->ensureTemplateCanBeAssigned($client, $data, $hourTemplate);

        DB::transaction(fn () => $hourTemplate->update($data));

        return back()->with('success', 'Plantilla de horario actualizada correctamente');
    }

    public function destroyAssignedHourTemplate(Client $client, string $template)
    {
        $hourTemplate = $client->assignedHoursTemplates()->findOrFail($template);

        $originalDateStart = $hourTemplate->date_start;
        if (! $originalDateStart) {
            $firstAssignedHour = $hourTemplate->assignedHours()->orderBy('date', 'asc')->first();
            if ($firstAssignedHour) {
                $originalDateStart = $firstAssignedHour->date;
            }
        }

        DB::transaction(function () use ($hourTemplate, $originalDateStart) {
            if (! $originalDateStart || ! $originalDateStart->lessThan(today())) {
                if ($hourTemplate->assignedHours()->whereHas('timeRecords')->exists()) {
                    throw ValidationException::withMessages([
                        'date_start' => __('employees.assigned_hour_locked'),
                    ]);
                }

                $hourTemplate->assignedHours()->delete();
                $hourTemplate->delete();

                return;
            }

            $yesterday = today()->subDay();
            $futureHours = $hourTemplate->assignedHours()->where('date', '>', $yesterday->toDateString());
            if ((clone $futureHours)->whereHas('timeRecords')->exists()) {
                throw ValidationException::withMessages([
                    'date_start' => __('employees.assigned_hour_locked'),
                ]);
            }

            $futureHours->delete();
            $hourTemplate->forceFill([
                'date_end' => $yesterday->toDateString(),
                'generated_until' => $yesterday->toDateString(),
            ])->saveQuietly();
        });

        return back()->with('success', 'Plantilla de horario eliminada correctamente');
    }
}
