<?php

namespace App\Http\Controllers\Api;

use App\Models\ContractTemplate;
use App\Http\Controllers\Controller;

class ContractTemplateController extends Controller
{
    public function index()
    {
        $contractTemplates = ContractTemplate::select(['id', 'name', 'content'])->get();

        return response()->json([
            'data' => $contractTemplates,
        ]);
    }
}
