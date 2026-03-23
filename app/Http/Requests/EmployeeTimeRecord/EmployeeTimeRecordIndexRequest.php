<?php

namespace App\Http\Requests\EmployeeTimeRecord;

use Illuminate\Foundation\Http\FormRequest;

class EmployeeTimeRecordIndexRequest extends FormRequest
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
            'filter_start' => 'nullable|date',
            'filter_end' => 'nullable|date',
            'export' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser employee_id.',
            'dir.in'  => 'El campo dir debe ser asc o desc.',
        ];
    }
}
