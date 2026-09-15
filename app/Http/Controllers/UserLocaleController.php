<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserLocaleController extends Controller
{
    /**
     * Store the preferred interface language for the authenticated user.
     */
    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'locale' => ['required', 'string', Rule::in(['es', 'ca'])],
        ]);

        $request->user()->update([
            'locale' => $data['locale'],
        ]);

        app()->setLocale($data['locale']);

        return back()->with('success', __('layout.language_updated'));
    }
}
