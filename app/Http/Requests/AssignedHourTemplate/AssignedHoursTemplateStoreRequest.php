<?php

namespace App\Http\Requests\AssignedHourTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AssignedHoursTemplateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'exists:services,id'],
            'employee_id' => ['required', 'exists:employees,id'],
            'recurrency' => ['required', Rule::in([0,1,2])],
            'days_of_week' => ['array', 'required_if:recurrency,1,2', Rule::in([0,1,2,3,4,5,6])],
            'days_of_week.*' => ['integer', Rule::in([0,1,2,3,4,5,6])],
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i', 'after:time_start'],
            'date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today', Rule::when(fn($input) => $input->recurrency === 0, 'required')],
            'date_start' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:today'],
            'date_end' => ['nullable', 'date_format:Y-m-d', 'after:date_start'],
        ];
    }
}
