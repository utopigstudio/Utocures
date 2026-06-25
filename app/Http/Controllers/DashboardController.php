<?php

namespace App\Http\Controllers;

use App\Models\Announcement;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->employee) {
            $employee = $user->employee;

            $active_work = $employee->timeRecords()->whereNull('date_out')->with(['assignedHour', 'assignedHour.timeRecords', 'assignedHour.client', 'assignedHour.service'])->first();

            $works = $employee->assignedHours()
                ->with(['service', 'client', 'timeRecords'])
                ->where('date', now()->toDateString())
                ->orderBy('time_start', 'asc')
                ->get();

            $announcements = Announcement::query()
                ->select(['id', 'title', 'image', 'content', 'created_at'])
                ->orderByDesc('created_at')
                ->get();

            return Inertia::render('EmployeeDashboard', [
                'works' => $works,
                'active_work' => $active_work,
                'announcements' => $announcements,
            ]);
        }

        return Inertia::render('Dashboard');
    }
}
