<?php

namespace App\Http\Controllers\Api;

use App\Models\Client;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    public function options(Request $request)
    {
        $data = $request->validate([
            'q' => 'nullable|string|min:3',
        ]);

        $q = $data['q'] ?? '';

        $clients = Client::select(['id', 'name', 'cif_nif'])
            ->when($q !== '', function ($query) use ($q) {
                $query->where('name', 'like', "%{$q}%")
                      ->orWhere('cif_nif', 'like', "%{$q}%");
            })
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $clients,
        ]);
    }
}
