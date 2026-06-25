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
use App\Models\Country;
use App\Models\Employee;
use App\Models\EmployeeTimeRecord;
use App\Models\Gender;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
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

        $employees = $employees->paginate(10)
            ->withQueryString();

        return Inertia::render('employees/List', [
            'data' => $employees,
            'sort' => $sort,
            'dir' => $dir,
        ]);
    }

    public function show(Employee $employee)
    {
        $employee->load(['notes', 'notes.user', 'services', 'user:id,name,email,avatar', 'assignedCharacteristics.characteristic']);
        $files = $employee->files()->get();
        $notes = $employee->notes()->get();
        $hours = $employee->assignedHours()
            ->with(['service', 'client', 'employee.user:id,name,avatar', 'timeRecords'])
            ->withCount('timeRecords')
            ->get();
        $statusPeriods = $employee->statusPeriods()
            ->with('updatedBy:id,name')
            ->orderByDesc('start_at')
            ->get();
        $activeStatusPeriod = $employee->activeStatusPeriodAt(now())?->loadMissing('updatedBy:id,name');
        $nextStatusPeriod = $employee->nextStatusPeriodAfter(now())?->loadMissing('updatedBy:id,name');
        $currentStatusCode = $employee->statusForMoment(now());

        return Inertia::render('employees/View', [
            'employee' => $employee,
            'files' => $files,
            'notes' => $notes,
            'hours' => $hours,
            'status_periods' => $statusPeriods,
            'current_status' => [
                'code' => $currentStatusCode,
                'label' => Employee::statusLabel($currentStatusCode),
            ],
            'active_status_period' => $activeStatusPeriod,
            'next_status_period' => $nextStatusPeriod,
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

    public function update(EmployeeStoreRequest $request, Employee $employee)
    {
        $data = $request->validated();
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
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

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
                'message' => 'No se encontró el registro.',
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

        $timeRecord = $work->timeRecords()
            ->whereNull('time_out')
            ->first();

        return response()->json([
            'time_record' => $timeRecord,
        ]);
    }

    public function storeTimeRecord(Request $request, AssignedHour $work)
    {
        $this->authorize('view', $work);

        $data = $request->validate([
            'date_in' => 'nullable|date_format:Y-m-d|required_without:date_out',
            'date_out' => 'nullable|date_format:Y-m-d|after_or_equal:date_in|required_without:date_in',
            'time_in' => 'nullable|date_format:H:i|required_without:time_out',
            'time_out' => 'nullable|date_format:H:i|after_or_equal:time_in|required_without:time_in',
        ]);

        $employee = $request->user()?->employee;

        $timeRecord = EmployeeTimeRecord::where('assigned_hour_id', $work->id)
            ->whereNull('time_out')
            ->orderBy('created_at', 'desc')
            ->first();

        if ($timeRecord) {
            $timeRecord->update([
                'date_out' => $data['date_out'],
                'time_out' => $data['time_out'],
            ]);
        } else {
            if (! $request->filled('time_in')) {
                return back()->withErrors(['time_in' => 'La hora de inicio es obligatoria al crear un nuevo registro de tiempo.']);
            }

            EmployeeTimeRecord::create([
                'assigned_hour_id' => $work->id,
                'employee_id' => $employee->id,
                'date_in' => $data['date_in'],
                'time_in' => $data['time_in'],
            ]);
        }

        return back()->with('success', 'Registro de tiempo almacenado correctamente.');
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

    public function hoursWorked()
    {
        $employee = Auth::user()?->employee;

        if (! $employee) {
            return response()->json([
                'message' => 'No se pudo obtener el empleado autenticado.',
            ], 400);
        }

        $time_records = $employee->timeRecords()
            ->with([
                'assignedHour',
                'assignedHour.timeRecords',
                'assignedHour.service',
                'assignedHour.client',
                'notes.user:id,name,avatar',
            ])
            ->orderBy('date_in', 'desc')
            ->get();

        return Inertia::render('employees/HoursWorked', [
            'time_records' => $time_records,
        ]);
    }
}
