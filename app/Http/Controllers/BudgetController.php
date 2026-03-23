<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Models\Budget;
use App\Http\Requests\Budget\BudgetIndexRequest;
use App\Http\Requests\Budget\BudgetStoreRequest;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Services\Document;
use App\Notifications\SendDocument;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    private const LIST_FIELDS = ['id', 'client_id', 'client_name', 'status', 'created_at', 'subtotal', 'discount', 'tax', 'total'];
    private const EXPORT_FIELDS = ['client_name', 'status', 'created_at', 'subtotal', 'discount', 'tax', 'total'];
    private const SEARCHABLE = ['client_name', 'client.cif_nif'];

    public function index(BudgetIndexRequest $request)
    {
        $statuses = Budget::getStatuses();
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = self::SEARCHABLE;
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_status' => $request->validated('filter_status'),
            'filter_year' => $request->validated('filter_year') ?? date('Y'),
        ];
        $export = $request->validated('export') ?? false;

        $budgets = Budget::select(self::LIST_FIELDS)
            ->with('client:id,name,cif_nif')
            ->year($filters['filter_year'])
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir);

        if ($export) {
            $budgets = $budgets->get();
            return Excel::download(new BaseExport($budgets, 'budgets', self::EXPORT_FIELDS), 'budgets.xlsx');
        }

        $budgets = $budgets->paginate(10)
            ->withQueryString();

        return Inertia::render('budgets/List', [
            'data' => $budgets,
            'statuses' => $statuses,
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

        $statuses = Budget::getStatuses();

        return Inertia::render('budgets/Form', [
            'statuses' => $statuses,
            'client_id' => $data['client_id'] ?? null,
        ]);
    }

    public function show(Budget $budget)
    {
        $budget = $budget->load(['lines', 'client']);
        $files = $budget->files;

        return Inertia::render('budgets/View', [
            'budget' => $budget,
            'files' => $files,
        ]);
    }

    public function edit(Budget $budget)
    {
        $budget = $budget->load(['lines']);
        $statuses = Budget::getStatuses();

        return Inertia::render('budgets/Form', [
            'budget' => $budget,
            'statuses' => $statuses
        ]);
    }

    public function store(BudgetStoreRequest $request)
    {
        $budget = Budget::create($request->validated());
        $budget->lines()->createMany($request->input('lines'));

        return to_route('budgets.index')->with('success', "Presupuesto {$budget->id} creado correctamente.");
    }

    public function update(BudgetStoreRequest $request, Budget $budget)
    {
        $budget->update($request->validated());
        $budget->lines()->delete();
        $budget->lines()->createMany($request->input('lines'));

        return to_route('budgets.index')->with('success', "Presupuesto {$budget->id} actualizado correctamente.");
    }

    public function destroy(Budget $budget)
    {
        $budget->delete();

        return to_route('budgets.index')->with('success', "Presupuesto {$budget->id} eliminado correctamente.");
    }

    public function downloadPdf(Budget $budget)
    {
        return $budget->downloadPdf();
    }

    public function sendByEmail(Budget $budget)
    {
        if (!$budget->client?->email) {
            return back()->with('error', __('budgets.email_not_found'));
        }

        $document = new Document($budget);
        $budget->client->notify(
            new SendDocument(__('budgets.send_mail_subject', ['company' => config('app.name')]), __('budgets.send_mail_message'), $document->pdf, $document->fileName)
        );

        if ($budget->status !== Budget::STATUS_ACCEPTED) {
            $budget->update(['status' => Budget::STATUS_SENT]);
        }

        return back()->with('success', __('budgets.email_sent_success'));
    }
}