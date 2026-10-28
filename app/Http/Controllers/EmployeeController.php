<?php

namespace App\Http\Controllers;

use App\Exports\BaseExport;
use App\Http\Requests\AvailableHour\AvailableHourStoreRequest;
use App\Http\Requests\Employee\EmployeeIndexRequest;
use App\Http\Requests\Employee\EmployeeStoreRequest;
use App\Http\Requests\Note\NoteIndexRequest;
use App\Models\AssignedHour;
use App\Models\AvailableHour;
use App\Models\Characteristic;
use App\Models\Configuration;
use App\Models\Country;
use App\Models\Employee;
use App\Models\EmployeeDocumentFolder;
use App\Models\EmployeeStatusPeriod;
use App\Models\EmployeeTimeRecord;
use App\Models\EmployeeTimeRecordLocation;
use App\Models\Gender;
use App\Models\Service;
use App\Models\User;
use App\Models\UserRole;
use App\Services\EmployeeStatusPeriodService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class EmployeeController extends Controller
{
    use AuthorizesRequests;

    private const LIST_FIELDS = ['id', 'nif', 'phone', 'user_id', 'created_at'];

    private const EXPORT_FIELDS = ['nif', 'user.name', 'user.email', 'phone', 'user.is_active', 'created_at'];

    public function index(EmployeeIndexRequest $request)
    {
        $sort = $request->validated('sort') ?? 'created_at';
        $dir = $request->validated('dir') ?? 'desc';
        $searchable = ['user.name', 'user.email', 'nif'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];
        $export = $request->validated('export') ?? false;

        $employees = Employee::select(self::LIST_FIELDS)
            ->with('user:id,name,email,is_active,avatar')
            ->filter($filters, $searchable)
            ->orderBy($sort, $dir);

        if ($export) {
            $employees = $employees->get();

            return Excel::download(new BaseExport($employees, 'employees', self::EXPORT_FIELDS), 'employees.xlsx');
        }

        $employees = $employees->paginate((int) ($request->validated('per_page') ?? 10))
            ->withQueryString();

        return Inertia::render('employees/List', [
            'data' => $employees,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function show(Request $request, Employee $employee, EmployeeStatusPeriodService $statusPeriodService): Response
    {
        $data = $request->validate(['filter_date' => 'nullable|date_format:Y-m-d']);
        $weekStart = Carbon::parse($data['filter_date'] ?? today()->toDateString())->startOfWeek(Carbon::MONDAY);
        $weekEnd = $weekStart->copy()->addDays(6);
        $periods = null;
        $statusPeriods = function () use ($employee, &$periods): Collection {
            return $periods ??= $employee->statusPeriods()
                ->with('updatedBy:id,name')
                ->orderByDesc('start_at')
                ->get();
        };
        $moment = now();
        $activeStatusPeriod = fn (): ?EmployeeStatusPeriod => $statusPeriods()->first(
            fn (EmployeeStatusPeriod $period): bool => $period->start_at->lte($moment) && $period->end_at->gte($moment),
        );

        return Inertia::render('employees/View', [
            'employee' => fn () => $employee->load(['services', 'user:id,name,email,avatar', 'assignedCharacteristics.characteristic']),
            'files' => fn () => $employee->files()->get(),
            'document_folders' => fn () => EmployeeDocumentFolder::orderBy('name')->get(['id', 'name']),
            'notes' => fn () => $employee->notes()->with('user:id,name,avatar')->get(),
            'hours' => fn () => $employee->assignedHours()
                ->whereBetween('date', [$weekStart->toDateString(), $weekEnd->toDateString()])
                ->with(['service', 'client:id,name', 'employee:id,user_id', 'employee.user:id,name,avatar', 'timeRecords'])
                ->withCount('timeRecords')
                ->get(),
            'calendar_date' => $weekStart->toDateString(),
            'status_periods' => $statusPeriods,
            'vacation_balances' => fn () => $statusPeriodService->vacationBalances($employee, $statusPeriods()),
            'current_status' => function () use ($activeStatusPeriod): array {
                $code = $activeStatusPeriod()?->type ?? Employee::STATUS_ACTIVE;

                return ['code' => $code, 'label' => Employee::statusLabel($code)];
            },
            'active_status_period' => $activeStatusPeriod,
            'next_status_period' => fn (): ?EmployeeStatusPeriod => $statusPeriods()->reverse()->first(fn (EmployeeStatusPeriod $period): bool => $period->start_at->gt($moment)),
        ]);
    }

    public function workShow(NoteIndexRequest $request, AssignedHour $work)
    {
        $this->authorize('view', $work);

        $searchable = ['content', 'user.name'];
        $filters = [
            'filter_search' => $request->validated('filter_search'),
        ];

        $work->load(['service', 'service.tasks', 'client', 'timeRecords']);
        $notes = $work->client->notes()
            ->with([
                'user:id,name,avatar',
                'employeeTimeRecord' => function ($query) {
                    $query->with([
                        'employee.user:id,name,avatar',
                        'assignedHour.service:id,name',
                    ]);
                },
            ])
            ->filter($filters, $searchable)
            ->orderByDesc('created_at')
            ->get();
        $hasActiveWork = $request->user()->employee->timeRecords()->whereNull('date_out')->exists();
        $currentTimeRecordId = $work->timeRecords()
            ->where('employee_id', $request->user()->employee->id)
            ->whereNull('date_out')
            ->latest('created_at')
            ->value('id');

        return Inertia::render('employees/WorkView', [
            'work' => $work,
            'hasActiveWork' => $hasActiveWork,
            'current_time_record_id' => $currentTimeRecordId,
            'notes' => $notes,
            'filters' => $filters,
        ]);
    }

    public function create()
    {
        $countries = Country::all();
        $genders = Gender::all();
        $services = Service::select('id', 'name')->get();
        $characteristics = Characteristic::with('options')->get();

        return Inertia::render('employees/Form', [
            'countries' => $countries,
            'genders' => $genders,
            'services' => $services,
            'characteristics' => $characteristics,
        ]);
    }

    public function edit(Employee $employee)
    {
        $countries = Country::all();
        $genders = Gender::all();
        $services = Service::select('id', 'name')->get();
        $characteristics = Characteristic::with('options')->get();

        $employee->load(['services', 'user:id,name,email,is_active,avatar', 'assignedCharacteristics.characteristic']);

        return Inertia::render('employees/Form', [
            'employee' => $employee,
            'countries' => $countries,
            'genders' => $genders,
            'services' => $services,
            'available_hours' => $employee->availableHours()->get(),
            'characteristics' => $characteristics,
        ]);
    }

    public function store(EmployeeStoreRequest $request)
    {
        $data = $request->validated();

        $user = User::withoutEvents(function () use ($data) {
            return User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => UserRole::EMPLOYEE,
                'password' => bcrypt(Str::random(40)),
                'is_active' => $data['is_active'],
                'avatar' => $data['avatar'] ?? null,
            ]);
        });

        $employee = Employee::create([
            ...$request->except(['name', 'email', 'is_active', 'avatar']),
            'user_id' => $user->id,
        ]);

        if ($request->filled('services')) {
            $employee->auditSync('services', $data['services']);
        }

        if ($request->filled('characteristics')) {
            $employee->auditSync('assignedCharacteristics', $data['characteristics']);
        }

        return to_route('employees.index')->with('success', "Empleado {$employee->name} creado correctamente.");
    }

    public function update(EmployeeStoreRequest $request, Employee $employee): RedirectResponse
    {
        return DB::transaction(function () use ($request, $employee): RedirectResponse {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            $data = $request->validated();
            if (isset($data['time_record_mode']) && $data['time_record_mode'] !== $employee->time_record_mode && $employee->timeRecords()->whereNull('time_out')->exists()) {
                throw ValidationException::withMessages(['time_record_mode' => __('employee_time_records.mode_open_record')]);
            }

            $employeeData = ['name' => $data['name'], 'email' => $data['email']];

            if ($request->has('avatar')) {
                $employeeData['avatar'] = $data['avatar'];
            }

            $employee->user->update($employeeData);

            if ($request->has('is_active') && $employee->user->is_active !== $data['is_active']) {
                $employee->user->update(['is_active' => $data['is_active']]);
            }

            $employee->update($request->except(['name', 'email', 'is_active', 'services', 'characteristics']));

            if ($request->filled('services')) {
                $employee->auditSync('services', $data['services']);
            } else {
                $employee->auditDetach('services');
            }

            if ($request->filled('characteristics')) {
                $employee->auditSync('assignedCharacteristics', $data['characteristics']);
            } else {
                $employee->auditDetach('assignedCharacteristics');
            }

            return to_route('employees.index')->with('success', "Empleado {$employee->name} actualizado correctamente.");
        });
    }

    public function destroy(Employee $employee)
    {
        DB::transaction(function () use ($employee) {
            $employee->user->deactivate();
            $employee->delete();
        });

        return to_route('employees.index')->with('success', "Empleado {$employee->name} eliminado correctamente.");
    }

    public function storeAssignedHour(AvailableHourStoreRequest $request, Employee $employee)
    {
        $employee->availableHours()->create($request->validated());

        return back()->with('success', 'Hora asignada correctamente.');
    }

    public function editAssignedHour(AvailableHourStoreRequest $request, Employee $employee, AvailableHour $hour)
    {
        $hour = $employee->availableHours()->where('id', $hour->id)->first();

        if (! $hour) {
            return response()->json([
                'message' => 'No se encontrÃ³ el registro.',
            ], 404);
        }

        $hour->update($request->validated());

        return back()->with('success', 'Hora actualizada correctamente.');
    }

    public function destroyAssignedHour(Employee $employee, AvailableHour $hour)
    {
        $deleted = $employee->availableHours()->where('id', $hour->id)->delete();

        if (! $deleted) {
            return response()->json([
                'message' => 'No se pudo eliminar el registro.',
            ], 400);
        }

        return back()->with('success', 'Hora eliminada correctamente.');
    }

    public function timeRecord(AssignedHour $work)
    {
        $this->authorize('view', $work);

        $employee = request()->user()?->employee;

        $timeRecord = $work->timeRecords()
            ->where('employee_id', $employee?->id)
            ->whereNull('time_out')
            ->latest('created_at')
            ->first();

        return response()->json([
            'time_record' => $timeRecord,
        ]);
    }

    public function storeTimeRecord(Request $request, AssignedHour $work)
    {
        abort_unless($request->user()?->employee?->time_record_mode === Employee::TIME_RECORD_WORK, 403);
        $this->authorize('view', $work);
        $employee = $request->user()?->employee;
        $locationRequired = (bool) Configuration::query()->value('require_time_record_location');

        $data = $request->validate([
            'location' => [$locationRequired ? 'required' : 'nullable', 'array'],
            'location.latitude' => [$locationRequired ? 'required' : 'nullable', 'numeric', 'between:-90,90'],
            'location.longitude' => [$locationRequired ? 'required' : 'nullable', 'numeric', 'between:-180,180'],
            'location.accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'location.position_captured_at' => ['nullable', 'date'],
        ]);

        if (! $employee) {
            return back()->withErrors(['employee' => 'No se pudo obtener la empleada autenticada.']);
        }

        DB::transaction(function () use ($data, $employee, $work): void {
            $employee = Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();
            abort_unless($employee->time_record_mode === Employee::TIME_RECORD_WORK, 403);
            $timeRecord = EmployeeTimeRecord::where('assigned_hour_id', $work->id)
                ->where('employee_id', $employee->id)->whereNull('time_out')->latest('created_at')->first();
            if (! $timeRecord && $employee->timeRecords()->whereNull('time_out')->exists()) {
                throw ValidationException::withMessages(['time_record' => __('employee_time_records.active_record_warning')]);
            }
            $recordedAt = now();

            if ($timeRecord) {
                $timeRecord->update([
                    'date_out' => $recordedAt->toDateString(),
                    'time_out' => $recordedAt->format('H:i:s'),
                ]);

                $this->storeTimeRecordLocation($timeRecord, EmployeeTimeRecordLocation::EVENT_OUT, $data['location'] ?? null);

                return;
            }

            $timeRecord = EmployeeTimeRecord::create([
                'assigned_hour_id' => $work->id,
                'employee_id' => $employee->id,
                'date_in' => $recordedAt->toDateString(),
                'time_in' => $recordedAt->format('H:i:s'),
            ]);

            $this->storeTimeRecordLocation($timeRecord, EmployeeTimeRecordLocation::EVENT_IN, $data['location'] ?? null);
        });

        return back()->with('success', 'Registro de tiempo almacenado correctamente.');
    }

    private function storeTimeRecordLocation(EmployeeTimeRecord $timeRecord, string $eventType, ?array $location): void
    {
        if (! $location) {
            return;
        }

        $timeRecord->locations()->updateOrCreate(
            ['event_type' => $eventType],
            [
                'latitude' => $location['latitude'],
                'longitude' => $location['longitude'],
                'accuracy_meters' => $location['accuracy_meters'] ?? null,
                'position_captured_at' => $location['position_captured_at'] ?? null,
            ]
        );
    }

    public function schedule(Request $request)
    {
        $employee = Auth::user()?->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'No se pudo obtener el empleado autenticado.',
            ], 400);
        }

        $data = $request->validate(['filter_date' => 'nullable|date_format:Y-m-d']);
        $selectedDate = isset($data['filter_date'])
            ? Carbon::createFromFormat('Y-m-d', $data['filter_date'])
            : now();

        $assigned_hours = $employee->assignedHours()
            ->where('date', $selectedDate->toDateString())
            ->with('service', 'employee.user', 'client', 'timeRecords')
            ->get();

        return Inertia::render('employees/Schedule', [
            'assigned_hours' => $assigned_hours,
            'selected_date' => $selectedDate->toDateString(),
        ]);
    }

    public function scheduleMonth(Request $request)
    {
        $employee = Auth::user()?->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'No se pudo obtener el empleado autenticado.',
            ], 400);
        }

        $data = $request->validate(['filter_date' => 'nullable|date_format:Y-m-d']);
        $selectedDate = isset($data['filter_date'])
            ? Carbon::createFromFormat('Y-m-d', $data['filter_date'])
            : now();

        $rangeStart = $selectedDate->copy()->startOfMonth();
        $rangeEnd = $selectedDate->copy()->endOfMonth();

        $assigned_hours = $employee->assignedHours()
            ->whereBetween('date', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with('service', 'client', 'timeRecords')
            ->orderBy('date')
            ->orderBy('time_start')
            ->get();

        return Inertia::render('employees/ScheduleMonth', [
            'assigned_hours' => $assigned_hours,
            'selected_date' => $selectedDate->toDateString(),
        ]);
    }

    public function hoursWorked(Request $request): Response|JsonResponse
    {
        $employee = Auth::user()?->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'No se pudo obtener el empleado autenticado.',
            ], 400);
        }

        $data = $request->validate([
            'filter_date' => 'nullable|date_format:Y-m-d',
            'filter_period' => 'nullable|in:monthly,yearly',
            'per_page' => 'nullable|integer|in:10,15,25,50,100',
            'sort' => 'nullable|in:date_in,date_out,time_in,time_out,created_at,id',
            'dir' => 'nullable|in:asc,desc',
        ]);
        $filters = [
            'filter_date' => $data['filter_date'] ?? today()->toDateString(),
            'filter_period' => $data['filter_period'] ?? 'monthly',
        ];
        $selectedDate = Carbon::parse($filters['filter_date']);
        $rangeStart = $filters['filter_period'] === 'yearly' ? $selectedDate->copy()->startOfYear() : $selectedDate->copy()->startOfMonth();
        $rangeEnd = $filters['filter_period'] === 'yearly' ? $selectedDate->copy()->endOfYear() : $selectedDate->copy()->endOfMonth();
        $sort = $data['sort'] ?? 'date_in';
        $dir = $data['dir'] ?? 'desc';

        $time_records = $employee->timeRecords()
            ->whereBetween('date_in', [$rangeStart->toDateString(), $rangeEnd->toDateString()])
            ->with([
                'assignedHour',
                'assignedHour.timeRecords',
                'assignedHour.service:id,name',
                'assignedHour.client:id,name',
                'notes.user:id,name,avatar',
            ])
            ->orderBy($sort, $dir)
            ->orderBy('id')
            ->paginate((int) ($data['per_page'] ?? 15))
            ->withQueryString();

        return Inertia::render('employees/HoursWorked', [
            'time_records' => $time_records,
            'filters' => $filters,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }
}
