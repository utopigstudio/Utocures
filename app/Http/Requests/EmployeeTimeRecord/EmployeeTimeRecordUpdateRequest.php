<?php

namespace App\Http\Requests\EmployeeTimeRecord;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class EmployeeTimeRecordUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_in' => ['required', 'date_format:Y-m-d'],
            'date_out' => ['nullable', 'required_with:time_out', 'date_format:Y-m-d', 'after_or_equal:date_in'],
            'time_in' => ['required', 'date_format:H:i'],
            'time_out' => ['nullable', 'required_with:date_out', 'date_format:H:i'],
            'modification_reason' => ['required', 'string', 'max:1000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->filled('date_out') || ! $this->filled('time_out')) {
                    return;
                }

                $start = Carbon::createFromFormat('Y-m-d H:i', $this->input('date_in').' '.$this->input('time_in'));
                $end = Carbon::createFromFormat('Y-m-d H:i', $this->input('date_out').' '.$this->input('time_out'));

                if ($end->lt($start)) {
                    $validator->errors()->add('time_out', 'La salida debe ser posterior o igual a la entrada.');
                }
            },
        ];
    }
}
