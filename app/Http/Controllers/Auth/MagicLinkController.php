<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use MagicLink\Actions\LoginAction;
use MagicLink\MagicLink;
use App\Models\User;
use App\Notifications\SendMagicLink;

class MagicLinkController extends Controller
{
    /**
     * Handle an incoming magic link request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function create(Request $request): RedirectResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $link = MagicLink::create(new LoginAction($user), null, 1)->url;
            $user->notify(new SendMagicLink($link));
        }

        return back()->with('success', 'Si la cuenta existe, se ha enviado un enlace mágico a su correo electrónico.');
    }
}
