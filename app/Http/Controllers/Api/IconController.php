<?php

namespace App\Http\Controllers\Api;

use App\Services\IconService;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IconController extends Controller
{
    public function index(Request $request, IconService $svc)
    {
        $collections = $request->filled('collections')
            ? explode(',', $request->string('collections'))
            : ['healthicons'];

        $q = (string) $request->get('q', '');
        $limit = (int) $request->get('limit', 120);
        $offset = (int) $request->get('offset', 0);
        $height = (string) $request->get('height', '24px');

        $icons = $svc->listIcons(
            collections: $collections,
            q: $q,
            limit: $limit,
            offset: $offset,
            withSvg: true,
            svgAttrs: ['height' => $height]
        );

        return response()->json([
            'data' => $icons,
        ]);
    }
}
