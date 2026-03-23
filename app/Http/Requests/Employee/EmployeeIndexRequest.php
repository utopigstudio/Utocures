<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'in:nif,name,email,phone,created_at',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string',
            'export' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser nif, name, email o phone.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
