<?php

namespace App\Http\Controllers;

use App\Support\ChangelogCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ChangelogController extends Controller
{
    public function content(Request $request): JsonResponse
    {
        $entries = ChangelogCatalog::entries(app()->getLocale(), $request->user()->role);

        return response()->json(['entries' => $entries])->header('Cache-Control', 'private, no-store');
    }

    public function markSeen(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:30'],
        ]);

        $request->user()->update(['last_seen_changelog_version' => $validated['version']]);

        return back();
    }
}
