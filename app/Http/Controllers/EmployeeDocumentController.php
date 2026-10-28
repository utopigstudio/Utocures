<?php

namespace App\Http\Controllers;

use App\Http\Requests\EmployeeDocument\EmployeeDocumentStoreRequest;
use App\Models\Employee;
use App\Models\EmployeeDocumentFolder;
use App\Models\File;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class EmployeeDocumentController extends Controller
{
    private const STORAGE_DIRECTORY = 'uploads/employee-documents';

    public function index(): Response
    {
        $employeeMorphType = (new Employee)->getMorphClass();

        $employees = Employee::query()
            ->select(['id', 'user_id'])
            ->with('user:id,name,email,is_active')
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get()
            ->sortBy(fn (Employee $employee) => $employee->user->name)
            ->values()
            ->map(fn (Employee $employee) => [
                'id' => $employee->id,
                'name' => $employee->user->name,
                'email' => $employee->user->email,
            ]);

        $documents = File::query()
            ->where('fileable_type', $employeeMorphType)
            ->where('path', 'like', self::STORAGE_DIRECTORY.'/%')
            ->selectRaw('MIN(id) as id, name, path, employee_document_folder_id, COUNT(*) as employee_count, MAX(created_at) as latest_created_at')
            ->groupBy('name', 'path', 'employee_document_folder_id')
            ->orderByDesc('latest_created_at')
            ->get()
            ->map(fn (File $file) => [
                'id' => $file->id,
                'name' => $file->name,
                'employee_document_folder_id' => $file->employee_document_folder_id,
                'employee_count' => (int) $file->employee_count,
                'created_at' => Carbon::parse($file->latest_created_at)->format('d/m/Y H:i'),
            ]);

        return Inertia::render('employeeDocuments/Index', [
            'employees' => $employees,
            'documents' => $documents,
            'folders' => EmployeeDocumentFolder::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(EmployeeDocumentStoreRequest $request): RedirectResponse
    {
        $employeeIds = $request->validated('employee_ids');
        $employees = Employee::query()
            ->whereIn('id', $employeeIds)
            ->whereHas('user', fn ($query) => $query->where('is_active', true))
            ->get();

        if ($employees->count() !== count($employeeIds)) {
            throw ValidationException::withMessages([
                'employee_ids' => __('employee_documents.validation_employee_invalid'),
            ]);
        }

        $folderId = $request->validated('employee_document_folder_id');
        $storedPaths = [];
        $documents = $request->file('documents');

        try {
            DB::transaction(function () use ($documents, $employees, $folderId, &$storedPaths) {
                foreach ($documents as $document) {
                    $path = $document->store(self::STORAGE_DIRECTORY, 'public');
                    $storedPaths[] = $path;
                    $now = now();

                    $rows = $employees->map(fn (Employee $employee) => [
                        'id' => (string) Str::uuid(),
                        'name' => $document->getClientOriginalName(),
                        'employee_document_folder_id' => $folderId,
                        'path' => $path,
                        'fileable_type' => $employee->getMorphClass(),
                        'fileable_id' => $employee->id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);

                    $rows->chunk(500)->each(fn ($chunk) => File::query()->insert($chunk->all()));
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($storedPaths);

            throw $exception;
        }

        return to_route('employee-documents.index')->with(
            'success',
            trans_choice('employee_documents.uploaded', count($documents), [
                'documents' => count($documents),
                'employees' => $employees->count(),
            ])
        );
    }

    public function download(File $file): BinaryFileResponse
    {
        $this->ensureBulkEmployeeDocument($file);

        abort_unless(Storage::disk('public')->exists($file->path), 404);

        return response()->download(Storage::disk('public')->path($file->path), $file->name);
    }

    public function destroy(File $file): RedirectResponse
    {
        $this->ensureBulkEmployeeDocument($file);

        DB::transaction(function () use ($file) {
            File::query()
                ->where('fileable_type', (new Employee)->getMorphClass())
                ->where('path', $file->path)
                ->where('name', $file->name)
                ->where('employee_document_folder_id', $file->employee_document_folder_id)
                ->delete();
        });

        if (! File::query()->where('path', $file->path)->exists()) {
            Storage::disk('public')->delete($file->path);
        }

        return back()->with('success', __('employee_documents.deleted'));
    }

    private function ensureBulkEmployeeDocument(File $file): void
    {
        $isBulkEmployeeDocument = $file->fileable_type === (new Employee)->getMorphClass()
            && Str::startsWith($file->path, self::STORAGE_DIRECTORY.'/');

        abort_unless($isBulkEmployeeDocument, 404);
    }
}
