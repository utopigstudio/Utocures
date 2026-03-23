<?php

namespace App\Http\Controllers\Api;

use App\Models\BudgetTemplate;
use App\Http\Controllers\Controller;

class BudgetTemplateController extends Controller
{
    public function index()
    {
        $budgetTemplates = BudgetTemplate::select(['id', 'name', 'content'])->get();

        return response()->json([
            'data' => $budgetTemplates,
        ]);
    }
}
