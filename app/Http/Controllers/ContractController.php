<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Models\Contract;
use App\Http\Requests\Contract\ContractIndexRequest;
use App\Http\Requests\Contract\ContractStoreRequest;
use App\Notifications\SendDocument;
use App\Services\Document;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class ContractController extends Controller
{
    private const LIST_FIELDS = ['id', 'client_id', 'status', 'title', 'content', 'date_start', 'date_end'];
    private const EXPORT_FIELDS = ['title', 'client.name', 'status', 'date_start', 'date_end'];
    private const SEARCHABLE = ['client.name', 'client.cif_nif'];

    public function index(ContractIndexRequest $request)
    {
        $statuses = Contract::getStatuses();
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = self::SEARCHABLE;
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_status' => $request->validated('filter_status'),
            'filter_year' => $request->validated('filter_year') ?? date('Y'),
        ];
        $export = $request->validated('export') ?? false;

        $contracts = Contract::select(self::LIST_FIELDS)
            ->with('client:id,name,cif_nif')
            ->year($filters['filter_year'])
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir);

        if ($export) {
            $contracts = $contracts->get();
            return Excel::download(new BaseExport($contracts, 'contracts', self::EXPORT_FIELDS), 'contracts.xlsx');
        }

        $contracts = $contracts->paginate(10)
            ->withQueryString();

        return Inertia::render('contracts/List', [
            'data' => $contracts,
            'statuses' => $statuses,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $statuses = Contract::getStatuses();

        return Inertia::render('contracts/Form', [
            'statuses' => $statuses
        ]);
    }

    public function show(Contract $contract)
    {
        $contract = $contract->load(['client']);
        $files = $contract->files;

        return Inertia::render('contracts/View', [
            'contract' => $contract,
            'files' => $files,
        ]);
    }

    public function edit(Contract $contract)
    {
        $contract = $contract->load(['client']);
        $statuses = Contract::getStatuses();

        return Inertia::render('contracts/Form', [
            'contract' => $contract,
            'statuses' => $statuses
        ]);
    }

    public function store(ContractStoreRequest $request)
    {
        $contract = Contract::create($request->validated());

        return to_route('contracts.index')->with('success', "Contrato {$contract->id} creado correctamente.");
    }

    public function update(ContractStoreRequest $request, Contract $contract)
    {
        $contract->update($request->validated());

        return to_route('contracts.index')->with('success', "Contrato {$contract->id} actualizado correctamente.");
    }

    public function destroy(Contract $contract)
    {
        $contract->delete();

        return to_route('contracts.index')->with('success', "Contrato {$contract->id} eliminado correctamente.");
    }

    public function downloadPdf(Contract $contract)
    {
        return $contract->downloadPdf();
    }

    public function sendByEmail(Contract $contract)
    {
        $document = new Document($contract);
        $contract->client->notify(
            new SendDocument(__('contracts.send_mail_subject', ['company' => config('app.name')]), __('contracts.send_mail_message'), $document->pdf, $document->fileName)
        );

        return back()->with('success', "Contrato enviado por correo correctamente.");
    }
}
