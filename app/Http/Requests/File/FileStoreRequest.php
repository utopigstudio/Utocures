<?php

namespace App\Http\Requests\File;

use Illuminate\Foundation\Http\FormRequest;

class FileStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg,png,pdf,doc,docx,pdf'],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $uploaded = $this->file('file');

        $path = $uploaded->store('uploads/files', 'public');

        return [
            'name' => $uploaded->getClientOriginalName(),
            'path' => $path,
        ];
    }
}
