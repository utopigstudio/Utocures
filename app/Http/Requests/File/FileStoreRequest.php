<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Arr;

class FileStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_document_folder_id' => [$this->query('resource') === 'employee' ? 'required' : 'prohibited', 'uuid', 'exists:employee_document_folders,id'],
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,pdf'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $validated = parent::validated();
        $uploaded = $this->file('file');

        $path = $uploaded->store('uploads/files', 'public');

        return array_merge(Arr::only($validated, ['employee_document_folder_id']), [
            'name' => $uploaded->getClientOriginalName(),
            'path' => $path,
        ]);
    }
}
