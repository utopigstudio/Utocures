<?php

namespace App\Http\Controllers;

use App\Exports\EmployeeTimeRecordExport;
use App\Http\Requests\EmployeeTimeRecord\EmployeeTimeRecordIndexRequest;
use App\Http\Requests\EmployeeTimeRecord\EmployeeTimeRecordUpdateRequest;
use App\Models\Client;
use App\Models\Employee;
use App\Models\EmployeeTimeRecord;
use App\Models\Note;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use OwenIt\Auditing\Events\AuditCustom;
use OwenIt\Auditing\Models\Audit;

class EmployeeTimeRecordController extends Controller
{
    public function index(EmployeeTimeRecordIndexRequest $request)
    {
        $view = $request->validated('view') ?? 'list';
        $employeeOptions = $this->employeeOptions();

        if ($view === 'calendar') {
            return $this->indexCalendar($request, $employeeOptions);
        }

        $requestedSort = $request->validated('sort');
        $requestedDir = $request->validated('dir');
        $sort = $requestedSort ?? 'id';
        $dir = $requestedDir ?? 'desc';
        $filters = [
            'filter_search' => $request->validated('filter_search'),
            'filter_start' => $request->validated('filter_start') ?? Carbon::now()->startOfMonth()->toDateString(),
            'filter_end' => $request->validated('filter_end') ?? Carbon::now()->endOfMonth()->toDateString(),
            'group_by' => $request->validated('group_by') ?? 'employee',
            'view' => 'list',
        ];
        $export = $request->validated('export') ?? false;

        if ($export) {
            return $this->downloadExport($filters);
        }

        if ($filters['group_by'] === 'client') {
            return $this->indexByClient($filters, $sort, $dir, $requestedSort, $requestedDir, $employeeOptions);
        }

        return $this->indexByEmployee($filters, $sort, $dir, $requestedSort, $requestedDir, $employeeOptions);
    }

    public function update(EmployeeTimeRecordUpdateRequest $request, EmployeeTimeRecord $employeeTimeRecord)
    {
        $data = $request->validated();
        $reason = $data['modification_reason'];
        unset($data['modification_reason']);

        $originalValues = $this->trackedTimeRecordValues($employeeTimeRecord);
        $updatedValues = $this->normalizeTimeRecordValues($data);

        EmployeeTimeRecord::withoutAuditing(function () use ($employeeTimeRecord, $updatedValues) {
            $employeeTimeRecord->update($updatedValues);
        });

        $changedOldValues = [];
        $changedNewValues = [];

        foreach ($updatedValues as $field => $newValue) {
            $oldValue = $originalValues[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changedOldValues[$field] = $oldValue;
                $changedNewValues[$field] = $newValue;
            }
        }

        $employeeTimeRecord->refresh();
        $employeeTimeRecord->auditEvent = 'updated';
        $employeeTimeRecord->isCustomEvent = true;
        $employeeTimeRecord->auditCustomOld = $changedOldValues;
        $employeeTimeRecord->auditCustomNew = [
            ...$changedNewValues,
            'modification_reason' => $reason,
        ];

        Event::dispatch(new AuditCustom($employeeTimeRecord));

        return back()->with('success', 'Registro de tiempo actualizado correctamente.');
    }

    private function indexByEmployee(array $filters, string $sort, string $dir, ?string $requestedSort, ?string $requestedDir, array $employeeOptions)
    {
        $employees = $this->employeeTimeRecordEmployeesQuery($filters)
            ->filter($filters, ['user.name'])
            ->orderBy($sort, $dir);

        $employees = $this->transformEmployeePaginator($employees->paginate(15)->withQueryString());

        return Inertia::render('employeeTimeRecords/List', [
            'view' => 'list',
            'data' => $employees,
            'filters' => $filters,
            'sort' => $requestedSort,
            'dir' => $requestedDir,
            'employee_options' => $employeeOptions,
            'calendar' => null,
        ]);
    }

    private function indexByClient(array $filters, string $sort, string $dir, ?string $requestedSort, ?string $requestedDir, array $employeeOptions)
    {
        $clients = $this->employeeTimeRecordClientsQuery($filters)
            ->filter($filters, ['name'])
            ->orderBy($sort, $dir);

        $clients = $this->transformClientPaginator($clients->paginate(15)->withQueryString());

        return Inertia::render('employeeTimeRecords/List', [
            'view' => 'list',
            'data' => $clients,
            'filters' => $filters,
            'sort' => $requestedSort,
            'dir' => $requestedDir,
            'employee_options' => $employeeOptions,
            'calendar' => null,
        ]);
    }

    private function indexCalendar(EmployeeTimeRecordIndexRequest $request, array $employeeOptions)
    {
        $selectedDate = $request->validated('filter_date') ?? Carbon::now()->toDateString();
        $selectedDateCarbon = Carbon::createFromFormat('Y-m-d', $selectedDate);
        $selectedEmployeeId = $request->validated('employee_id') ?? ($employeeOptions[0]['value'] ?? null);

        $selectedEmployee = $selectedEmployeeId
            ? Employee::query()->with('user:id,name,avatar')->find($selectedEmployeeId)
            : null;

        $records = collect();

        if ($selectedEmployee) {
            $records = EmployeeTimeRecord::query()
                ->whereBelongsTo($selectedEmployee)
                ->whereBetween('date_in', [
                    $selectedDateCarbon->copy()->startOfMonth()->toDateString(),
                    $selectedDateCarbon->copy()->endOfMonth()->toDateString(),
                ])
                ->with([
                    'assignedHour.client:id,name',
                    'assignedHour.service:id,name',
                    'notes.user:id,name,avatar',
                ])
                ->orderBy('date_in')
                ->orderBy('time_in')
                ->get();
        }

        $latestAuditsByRecordId = $this->latestAuditsByRecordId($records);

        return Inertia::render('employeeTimeRecords/List', [
            'view' => 'calendar',
            'data' => null,
            'filters' => [
                'view' => 'calendar',
                'employee_id' => $selectedEmployeeId,
                'filter_date' => $selectedDate,
            ],
            'sort' => null,
            'dir' => null,
            'employee_options' => $employeeOptions,
            'calendar' => [
                'selected_employee_id' => $selectedEmployeeId,
                'selected_employee' => $selectedEmployee ? [
                    'id' => $selectedEmployee->id,
                    'name' => $selectedEmployee->user?->name,
                    'avatar' => $selectedEmployee->user?->avatar,
                ] : null,
                'selected_date' => $selectedDate,
                'records' => $this->transformCalendarRecords($records, $latestAuditsByRecordId),
            ],
        ]);
    }

    private function transformEmployeePaginator(LengthAwarePaginator $employees): LengthAwarePaginator
    {
        return $employees->through(fn ($employee) => $this->mapEmployeeWithGroupedDetails($employee));
    }

    private function transformEmployeeCollection(EloquentCollection $employees): EloquentCollection
    {
        return $employees->map(fn ($employee) => $this->mapEmployeeWithGroupedDetails($employee));
    }

    private function transformClientPaginator(LengthAwarePaginator $clients): LengthAwarePaginator
    {
        return $clients->through(fn ($client) => $this->mapClientWithGroupedDetails($client));
    }

    private function transformClientCollection(EloquentCollection $clients): EloquentCollection
    {
        return $clients->map(fn ($client) => $this->mapClientWithGroupedDetails($client));
    }

    private function mapEmployeeWithGroupedDetails(Employee $employee): Employee
    {
        $groupedDetails = $employee->assignedHours
            ->groupBy(fn ($item) => $item->client_id.'-'.$item->service_id)
            ->map(function ($assignedHours) {
                $client = $assignedHours->first()->client;
                $service = $assignedHours->first()->service;

                return [
                    'detail_key' => $client->id.'-'.$service->id,
                    'client_name' => $client->name,
                    'service_name' => $service->name,
                    'programmed_hours' => $this->formatMinutes($assignedHours->sum('programmed_hours')),
                    'registered_hours' => $this->formatMinutes($assignedHours->sum('registered_hours')),
                    'incident_notes' => $this->mapIncidentNotes($assignedHours),
                ];
            })
            ->values();

        $employee->grouped_details = $groupedDetails;

        return $employee->append(['programmed_hours', 'registered_hours']);
    }

    private function mapClientWithGroupedDetails(Client $client): Client
    {
        $groupedDetails = $client->assignedHours
            ->groupBy(fn ($item) => $item->employee_id.'-'.$item->service_id)
            ->map(function ($assignedHours) {
                $employee = $assignedHours->first()->employee;
                $service = $assignedHours->first()->service;

                return [
                    'detail_key' => $employee->id.'-'.$service->id,
                    'employee_name' => $employee->user?->name,
                    'employee_avatar' => $employee->user?->avatar,
                    'service_name' => $service->name,
                    'programmed_hours' => $this->formatMinutes($assignedHours->sum('programmed_hours')),
                    'registered_hours' => $this->formatMinutes($assignedHours->sum('registered_hours')),
                    'incident_notes' => $this->mapIncidentNotes($assignedHours),
                ];
            })
            ->values();

        $client->grouped_details = $groupedDetails;
        $client->programmed_hours = $this->formatMinutes($client->assignedHours->sum('programmed_hours'));
        $client->registered_hours = $this->formatMinutes($client->assignedHours->sum('registered_hours'));

        return $client;
    }

    private function formatMinutes(int $minutes): string
    {
        return sprintf('%02d:%02d', floor($minutes / 60), $minutes % 60);
    }

    private function downloadExport(array $filters)
    {
        $employees = $this->transformEmployeeCollection($this->employeeTimeRecordEmployeesQuery($filters)->get());
        $clients = $this->transformClientCollection($this->employeeTimeRecordClientsQuery($filters)->get());
        $exportPreRows = [
            ['Fecha inicio:', $filters['filter_start']],
            ['Fecha fin:', $filters['filter_end']],
        ];

        return Excel::download(
            new EmployeeTimeRecordExport($employees, $clients, $exportPreRows),
            'time_records.xlsx'
        );
    }

    private function employeeTimeRecordEmployeesQuery(array $filters)
    {
        return Employee::query()
            ->with([
                'timeRecords' => function ($query) use ($filters) {
                    $query->where('date_in', '>=', $filters['filter_start'])
                        ->where('date_out', '<=', $filters['filter_end']);
                },
                'assignedHours' => function ($query) use ($filters) {
                    $query->whereBetween('date', [$filters['filter_start'], $filters['filter_end']])
                        ->with([
                            'service:id,name',
                            'client:id,name',
                            'timeRecords' => function ($timeRecordsQuery) use ($filters) {
                                $timeRecordsQuery->where('date_in', '>=', $filters['filter_start'])
                                    ->where('date_out', '<=', $filters['filter_end'])
                                    ->with([
                                        'notes.user:id,name,avatar',
                                        'employee.user:id,name,avatar',
                                    ]);
                            },
                        ]);
                },
                'user:id,name,avatar',
            ]);
    }

    private function employeeTimeRecordClientsQuery(array $filters)
    {
        return Client::query()
            ->with([
                'assignedHours' => function ($query) use ($filters) {
                    $query->whereBetween('date', [$filters['filter_start'], $filters['filter_end']])
                        ->with([
                            'service',
                            'employee.user:id,name,avatar',
                            'timeRecords' => function ($timeRecordsQuery) use ($filters) {
                                $timeRecordsQuery->where('date_in', '>=', $filters['filter_start'])
                                    ->where('date_out', '<=', $filters['filter_end'])
                                    ->with([
                                        'notes.user:id,name,avatar',
                                        'employee.user:id,name,avatar',
                                    ]);
                            },
                        ]);
                },
            ]);
    }

    private function mapIncidentNotes(EloquentCollection $assignedHours): array
    {
        return $assignedHours
            ->flatMap(fn ($assignedHour) => $assignedHour->timeRecords)
            ->flatMap(function ($timeRecord) {
                return $timeRecord->notes
                    ->where('type', Note::TYPE_INCIDENT)
                    ->map(function ($note) use ($timeRecord) {
                        return [
                            'id' => $note->id,
                            'content' => $note->content,
                            'created_at' => $note->created_at,
                            'employee_name' => $timeRecord->employee?->user?->name,
                            'record_date' => $timeRecord->date_in?->format('d-m-Y'),
                            'record_time_in' => $timeRecord->time_in,
                            'record_time_out' => $timeRecord->time_out,
                        ];
                    });
            })
            ->unique('id')
            ->values()
            ->all();
    }

    private function transformCalendarRecords(EloquentCollection $records, Collection $latestAuditsByRecordId): array
    {
        return $records
            ->map(function (EmployeeTimeRecord $timeRecord) use ($latestAuditsByRecordId) {
                $latestAudit = $latestAuditsByRecordId->get($timeRecord->id);

                return [
                    'id' => $timeRecord->id,
                    'date_in' => $timeRecord->getRawOriginal('date_in'),
                    'date_out' => $timeRecord->getRawOriginal('date_out'),
                    'time_in' => $this->formatTime($timeRecord->time_in),
                    'time_out' => $this->formatTime($timeRecord->time_out),
                    'date_in_formatted' => $timeRecord->date_in?->format('d/m/Y'),
                    'date_out_formatted' => $timeRecord->date_out?->format('d/m/Y'),
                    'client_name' => $timeRecord->assignedHour?->client?->name,
                    'service_name' => $timeRecord->assignedHour?->service?->name,
                    'notes_count' => $timeRecord->notes->where('type', Note::TYPE_INCIDENT)->count(),
                    'latest_modification' => $latestAudit ? [
                        'audit_id' => $latestAudit->id,
                        'reason' => data_get($latestAudit->new_values, 'modification_reason'),
                        'user_name' => $latestAudit->user?->name,
                        'created_at_formatted' => $latestAudit->created_at->format('d/m/Y H:i:s'),
                    ] : null,
                ];
            })
            ->values()
            ->all();
    }

    private function latestAuditsByRecordId(EloquentCollection $records): Collection
    {
        $recordIds = $records->pluck('id')->all();

        if ($recordIds === []) {
            return collect();
        }

        return Audit::query()
            ->where('auditable_type', EmployeeTimeRecord::class)
            ->where('event', 'updated')
            ->whereIn('auditable_id', $recordIds)
            ->with('user:id,name')
            ->latest('created_at')
            ->get()
            ->filter(fn (Audit $audit) => filled(data_get($audit->new_values, 'modification_reason')))
            ->unique('auditable_id')
            ->keyBy('auditable_id');
    }

    private function employeeOptions(): array
    {
        return Employee::query()
            ->with('user:id,name')
            ->get()
            ->filter(fn (Employee $employee) => $employee->user)
            ->sortBy(fn (Employee $employee) => $employee->user?->name)
            ->values()
            ->map(fn (Employee $employee) => [
                'value' => $employee->id,
                'label' => $employee->user?->name,
            ])
            ->all();
    }

    private function trackedTimeRecordValues(EmployeeTimeRecord $employeeTimeRecord): array
    {
        return [
            'date_in' => $employeeTimeRecord->getRawOriginal('date_in'),
            'date_out' => $employeeTimeRecord->getRawOriginal('date_out'),
            'time_in' => $this->formatTime($employeeTimeRecord->time_in),
            'time_out' => $this->formatTime($employeeTimeRecord->time_out),
        ];
    }

    private function normalizeTimeRecordValues(array $data): array
    {
        return [
            'date_in' => $data['date_in'],
            'date_out' => $data['date_out'] ?? null,
            'time_in' => $data['time_in'],
            'time_out' => $data['time_out'] ?? null,
        ];
    }

    private function formatTime(?string $time): ?string
    {
        return $time ? substr($time, 0, 5) : null;
    }
}
