<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AdminNotificationController extends Controller
{
    public function update(Request $request, AdminNotification $adminNotification): RedirectResponse
    {
        $adminNotification->markAsRead();

        return back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        AdminNotification::query()
            ->where('read_at', '=', null)
            ->update([
                'read_at' => now(),
                'updated_at' => now(),
            ]);

        return back();
    }
}
