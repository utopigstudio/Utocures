<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Http\Requests\Invoice\InvoiceIndexRequest;
use App\Http\Requests\Invoice\InvoiceStoreRequest;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class InvoiceController extends Controller
{
    private const LIST_FIELDS = ['id', 'client_id', 'billing_recipient', 'date', 'subtotal', 'discount', 'total', 'created_at'];

    private const EXPORT_FIELDS = ['client.name', 'billing_recipient.name', 'billing_recipient.cif_nif', 'billing_recipient.address', 'billing_recipient.zip_code', 'billing_recipient.city', 'billing_recipient.country_id', 'billing_recipient.email', 'date', 'subtotal', 'discount', 'total', 'created_at'];

    public function index(InvoiceIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['client.name', 'client.cif_nif', 'billing_recipient->name', 'billing_recipient->cif_nif'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_year' => $request->validated('filter_year') ?? date('Y'),
        ];
        $export = $request->validated('export') ?? false;

        $invoices = Invoice::select(self::LIST_FIELDS)
            ->with([
                'client' => function ($query) {
                    $query->withTrashed()->select('id', 'name', 'cif_nif', 'deleted_at');
                },
            ])
            ->year($filters['filter_year'])
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir);

        if ($export) {
            $invoices = $invoices->get();

            return Excel::download(new BaseExport($invoices, 'invoices', self::EXPORT_FIELDS), 'invoices.xlsx');
        }

        $invoices = $invoices->paginate((int) ($request->validated('per_page') ?? 10))
            ->withQueryString();

        return Inertia::render('invoices/List', [
            'data' => $invoices,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['nullable', 'exists:clients,id'],
            'recipient_client_id' => ['nullable', 'exists:clients,id'],
        ]);

        $clientId = $data['recipient_client_id'] ?? $data['client_id'] ?? null;

        return Inertia::render('invoices/Form', [
            'client_id' => $clientId,
            'billing_recipient' => $clientId ? Client::findOrFail($clientId)->billingRecipientData() : null,
        ]);
    }

    public function edit(Request $request, Invoice $invoice)
    {
        $data = $request->validate(['recipient_client_id' => ['nullable', 'exists:clients,id']]);
        $invoice = $invoice->load(['lines', 'client']);
        $clientId = $data['recipient_client_id'] ?? $invoice->client_id;

        return Inertia::render('invoices/Form', [
            'invoice' => $invoice,
            'client_id' => $clientId,
            'billing_recipient' => $clientId === $invoice->client_id
                ? $invoice->billing_recipient
                : Client::findOrFail($clientId)->billingRecipientData(),
        ]);
    }

    public function store(InvoiceStoreRequest $request)
    {
        $invoice = DB::transaction(function () use ($request): Invoice {
            $invoice = Invoice::create($request->safe()->except('lines'));
            $invoice->lines()->createMany($request->validated('lines'));

            return $invoice;
        });

        return to_route('invoices.index')->with('success', "Factura {$invoice->id} creada correctamente.");
    }

    public function update(InvoiceStoreRequest $request, Invoice $invoice)
    {
        DB::transaction(function () use ($request, $invoice): void {
            $invoice->update($request->safe()->except('lines'));
            $invoice->lines()->delete();
            $invoice->lines()->createMany($request->validated('lines'));
        });

        return to_route('invoices.index')->with('success', "Factura {$invoice->id} actualizada correctamente.");
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return to_route('invoices.index')->with('success', "Factura {$invoice->id} eliminada correctamente.");
    }
}
