<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Models\Invoice;
use App\Http\Requests\Invoice\InvoiceIndexRequest;
use App\Http\Requests\Invoice\InvoiceStoreRequest;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    private const LIST_FIELDS = ['id', 'client_id', 'date', 'subtotal', 'discount', 'total', 'client_id', 'created_at'];
    private const EXPORT_FIELDS = ['client.name', 'date', 'subtotal', 'discount', 'total', 'created_at'];

    public function index(InvoiceIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['client.name', 'client.cif_nif'];
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

        $invoices = $invoices->paginate(10)
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
        ]);

        return Inertia::render('invoices/Form', [
            'client_id' => $data['client_id'] ?? null,
        ]);
    }

    public function edit(Invoice $invoice)
    {
        $invoice = $invoice->load(['lines', 'client']);

        return Inertia::render('invoices/Form', [
            'invoice' => $invoice,
        ]);
    }

    public function store(InvoiceStoreRequest $request)
    {
        $invoice = Invoice::create($request->validated());
        $invoice->lines()->createMany($request->input('lines'));

        return to_route('invoices.index')->with('success', "Factura {$invoice->id} creada correctamente.");
    }

    public function update(InvoiceStoreRequest $request, Invoice $invoice)
    {
        $invoice->update($request->validated());
        $invoice->lines()->delete();
        $invoice->lines()->createMany($request->input('lines'));

        return to_route('invoices.index')->with('success', "Factura {$invoice->id} actualizada correctamente.");
    }

    public function destroy(Invoice $invoice)
    {
        $invoice->delete();

        return to_route('invoices.index')->with('success', "Factura {$invoice->id} eliminada correctamente.");
    }
}
