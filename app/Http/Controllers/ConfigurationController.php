<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Configuration;
use App\Models\Country;
use App\Http\Requests\Configuration\ConfigurationStoreRequest;

class ConfigurationController extends Controller
{
    public function index()
    {
        $configuration = Configuration::first();

        return Inertia::render('configuration/Form', [
            'configuration' => $configuration,
            'countries' => Country::all(),
        ]);
    }

    public function update(ConfigurationStoreRequest $request, Configuration $configuration)
    {
        $configuration->update($request->validated());

        return back()->with('success', 'Configuración guardada con éxito.');
    }
}
