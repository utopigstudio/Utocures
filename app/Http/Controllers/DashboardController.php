<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->employee) {
            $active_work = $user->employee->timeRecords()->whereNull('date_out')->with(['assignedHour', 'assignedHour.client', 'assignedHour.service'])->first();

            $works = $user->employee->assignedHours()
                ->with(['service', 'client'])
                ->whereDate('date', '=', now()->startOfDay())
                ->orderBy('time_start', 'asc')
                ->get();

            return Inertia::render('EmployeeDashboard', [
                'works' => $works,
                'active_work' => $active_work,
            ]);
        }

        return Inertia::render('Dashboard');
    }
}
