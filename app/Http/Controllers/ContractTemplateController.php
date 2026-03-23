<?php

namespace App\Http\Controllers;

use App\Models\ContractTemplate;
use App\Http\Requests\ContractTemplate\ContractTemplateIndexRequest;
use App\Http\Requests\ContractTemplate\ContractTemplateStoreRequest;
use Inertia\Inertia;

class ContractTemplateController extends Controller
{
    private const LIST_FIELDS = ['id', 'name', 'created_at'];

    public function index(ContractTemplateIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['name'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];

        $contractTemplates = ContractTemplate::select(self::LIST_FIELDS)
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('contractTemplates/List', [
            'data' => $contractTemplates,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('contractTemplates/Form');
    }

    public function edit(ContractTemplate $contractTemplate)
    {
        return Inertia::render('contractTemplates/Form', [
            'contract_template' => $contractTemplate,
        ]);
    }

    public function store(ContractTemplateStoreRequest $request)
    {
        $contractTemplate = ContractTemplate::create($request->validated());

        return to_route('contract-templates.index')->with('success', "Plantilla de contrato {$contractTemplate->name} creada con éxito.");
    }

    public function update(ContractTemplateStoreRequest $request, ContractTemplate $contractTemplate)
    {
        $contractTemplate->update($request->validated());

        return to_route('contract-templates.index')->with('success', "Plantilla de contrato {$contractTemplate->name} actualizada con éxito.");
    }

    public function destroy(ContractTemplate $contractTemplate)
    {
        $name = $contractTemplate->name;
        $contractTemplate->delete();

        return to_route('contract-templates.index')->with('success', "Plantilla de contrato {$name} eliminada con éxito.");
    }
}
