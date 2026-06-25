<?php

namespace App\Http\Requests\AssignedHourTemplate;

use App\Models\AssignedHoursTemplate;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class AssignedHoursTemplateUpdateRequest extends FormRequest
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
            'date' => ['requiredIf:recurrency,0', 'date_format:Y-m-d'],
            'date_start' => ['nullable', 'date_format:Y-m-d'],
            'date_end' => ['nullable', 'date_format:Y-m-d', 'after:date_start'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $dateStart = $this->input('date_start');
                $templateId = $this->route('template'); // This matches the controller parameter

                $existing = AssignedHoursTemplate::findOrFail($templateId);

                $originalDateStart = $existing->date_start?->format('Y-m-d');

                if ($dateStart && $dateStart !== $originalDateStart) {
                    $today = Carbon::today()->format('Y-m-d');

                    if ($dateStart < $today) {
                        $validator->errors()->add('date_start', __('The start date must be today or a future date.'));
                    }
                }
            },
        ];
    }

}
