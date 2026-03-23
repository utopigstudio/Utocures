<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Models\Employee;
use App\Http\Requests\EmployeeTimeRecord\EmployeeTimeRecordIndexRequest;
use Carbon\Carbon;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeTimeRecordController extends Controller
{
    const EXPORT_FIELDS = ['user.name', 'programmed_hours', 'registered_hours'];

    public function index(EmployeeTimeRecordIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'id';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['user.name'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_start' => $request->validated('filter_start') ?? Carbon::now()->startOfMonth()->toDateString(),
            'filter_end' => $request->validated('filter_end') ?? Carbon::now()->endOfMonth()->toDateString(),
        ];
        $export = $request->validated('export') ?? false;

        $employees = Employee::query()
            ->with([
                'timeRecords' => function ($query) use ($filters) {
                    $query->where('date_in', '>=', $filters['filter_start'])
                        ->where('date_out', '<=', $filters['filter_end']);
                },
                'assignedHours' => function ($query) use ($filters) {
                    $query->whereBetween('date', [$filters['filter_start'], $filters['filter_end']])
                        ->with('service', 'client');
                },
                'user:id,name,avatar',
            ])
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir);

        if ($export) {
            $employees = $employees->get()
                ->each->append([
                    'programmed_hours',
                    'registered_hours',
                ]);

            $export_pre_rows = [
                ['Fecha inicio:', $filters['filter_start']],
                ['Fecha fin:', $filters['filter_end']],
            ];

            return Excel::download(new BaseExport($employees, 'employees', self::EXPORT_FIELDS, $export_pre_rows), 'time_records.xlsx');
        }

        $employees = $employees->paginate(15)
            ->through(function ($employee) {
                $grouped = $employee->assignedHours
                    ->groupBy(fn ($item) => $item->client_id.'-'.$item->service_id)
                    ->map(function ($assignedHours) {
                        $client = $assignedHours->first()->client;
                        $service = $assignedHours->first()->service;

                        $programmed_hours = $assignedHours->sum('programmed_hours');
                        $registered_hours = $assignedHours->sum('registered_hours');

                        return [
                            'client_name' => $client->name,
                            'service_name' => $service->name,
                            'programmed_hours' => sprintf('%02d:%02d', floor($programmed_hours / 60), $programmed_hours % 60),
                            'registered_hours' => sprintf('%02d:%02d', floor($registered_hours / 60), $registered_hours % 60),
                        ];
                    })
                    ->values();

                $employee->grouped_services = $grouped;

                return $employee->append([
                    'programmed_hours',
                    'registered_hours',
                ]);
            })
            ->withQueryString();

        return Inertia::render('employeeTimeRecords/List', [
            'data' => $employees,
            'filters' => $filters,
        ]);
    }
}
