<?php

namespace App\Http\Requests\AvailableHour;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AvailableHourStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'time_start' => ['required', 'date_format:H:i'],
            'time_end' => ['required', 'date_format:H:i'],
            'day_of_week' => ['required', Rule::in([0,1,2,3,4,5,6])],
        ];
    }
}
