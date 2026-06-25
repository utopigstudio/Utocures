<?php

namespace App\Http\Requests\Api\Employee;

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
            'assigned_hour_id' => ['nullable', 'string', 'exists:assigned_hours,id'],
            'days_of_week' => ['array'],
            'days_of_week.*' => ['integer', 'in:0,1,2,3,4,5,6'],
            'event_id' => ['nullable', 'string', 'exists:assigned_hours_templates,id'],
            'recurrency' => ['required', 'in:0,1,2'],
            'service_id' => ['required', 'string', 'exists:services,id'],
            'time_start' => ['required'],
            'time_end' => ['required'],
            'date' => ['required_if:recurrency,0', 'nullable', 'date'],
            'date_start' => ['nullable', 'date'],
            'date_end' => ['nullable', 'date', 'after_or_equal:date_start'],
        ];
    }
}
