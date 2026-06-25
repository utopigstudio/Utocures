<?php

namespace App\Http\Requests\Employee;

use App\Models\EmployeeStatusPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EmployeeStatusPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type' => ['required', Rule::in(array_keys(EmployeeStatusPeriod::TYPES))],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after:start_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'type' => __('employees.period_type'),
            'start_at' => __('employees.start_at'),
            'end_at' => __('employees.end_at'),
            'notes' => __('employees.observations'),
        ];
    }
}
