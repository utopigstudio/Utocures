<?php

namespace App\Http\Controllers;

use App\Models\BudgetTemplate;
use App\Http\Requests\BudgetTemplate\BudgetTemplateIndexRequest;
use App\Http\Requests\BudgetTemplate\BudgetTemplateStoreRequest;
use Inertia\Inertia;

class BudgetTemplateController extends Controller
{
    private const LIST_FIELDS = ['id', 'name', 'created_at'];

    public function index(BudgetTemplateIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['name'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];

        $budgetsTemplates = BudgetTemplate::select(self::LIST_FIELDS)
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir)
            ->paginate(10)
            ->withQueryString();

        return Inertia::render('budgetTemplates/List', [
            'data' => $budgetsTemplates,
            'sort' => $sort,
            'dir' => $dir,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        return Inertia::render('budgetTemplates/Form');
    }

    public function edit(BudgetTemplate $budgetTemplate)
    {
        return Inertia::render('budgetTemplates/Form', [
            'budget_template' => $budgetTemplate,
        ]);
    }

    public function store(BudgetTemplateStoreRequest $request)
    {
        $budgetTemplate = BudgetTemplate::create($request->validated());

        return to_route('budget-templates.index')->with('success', "Plantilla de presupuesto {$budgetTemplate->name} creada con éxito.");
    }
    
    public function update(BudgetTemplateStoreRequest $request, BudgetTemplate $budgetTemplate)
    {
        $budgetTemplate->update($request->validated());

        return to_route('budget-templates.index')->with('success', "Plantilla de presupuesto {$budgetTemplate->name} actualizada con éxito.");
    }

    public function destroy(BudgetTemplate $budgetTemplate)
    {
        $name = $budgetTemplate->name;
        $budgetTemplate->delete();

        return to_route('budget-templates.index')->with('success', "Plantilla de presupuesto {$name} eliminada con éxito.");
    }
}
