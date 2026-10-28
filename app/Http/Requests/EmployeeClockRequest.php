<?php

namespace App\Http\Requests;

use App\Models\Configuration;
use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;

class EmployeeClockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isEmployee()
            && $this->user()->employee?->time_record_mode === Employee::TIME_RECORD_FREE;
    }

    public function rules(): array
    {
        $required = (bool) Configuration::query()->value('require_time_record_location');

        return [
            'action' => ['required', 'in:in,out'],
            'time_record_id' => ['nullable', 'required_if:action,out', 'uuid'],
            'location' => [$required ? 'required' : 'nullable', 'array:latitude,longitude,accuracy_meters,position_captured_at'],
            'location.latitude' => [$required ? 'required' : 'required_with:location', 'numeric', 'between:-90,90'],
            'location.longitude' => [$required ? 'required' : 'required_with:location', 'numeric', 'between:-180,180'],
            'location.accuracy_meters' => ['nullable', 'numeric', 'min:0', 'max:999999.99'],
            'location.position_captured_at' => ['nullable', 'date'],
        ];
    }
}
