<?php

namespace App\Http\Controllers;

use App\Models\EmployeeDocumentFolder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeDocumentFolderController extends Controller
{
    private function validatedName(Request $request, ?EmployeeDocumentFolder $folder = null): array
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        return $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('employee_document_folders')->ignore($folder?->id)],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        EmployeeDocumentFolder::create($this->validatedName($request));

        return back()->with('success', __('employee_documents.folder_saved'));
    }

    public function update(Request $request, EmployeeDocumentFolder $folder): RedirectResponse
    {
        $folder->update($this->validatedName($request, $folder));

        return back()->with('success', __('employee_documents.folder_saved'));
    }

    public function destroy(EmployeeDocumentFolder $folder): RedirectResponse
    {
        DB::transaction(function () use ($folder) {
            $folder = EmployeeDocumentFolder::whereKey($folder->id)->lockForUpdate()->firstOrFail();
            if ($folder->files()->exists()) {
                throw ValidationException::withMessages(['folder' => __('employee_documents.folder_not_empty')]);
            }
            $folder->delete();
        });

        return back()->with('success', __('employee_documents.folder_deleted'));
    }
}
