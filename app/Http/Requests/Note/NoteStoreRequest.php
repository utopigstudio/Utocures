<?php

namespace App\Http\Requests\Note;

use App\Models\Note;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class NoteStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => ['required', 'string', 'max:500'],
            'type' => ['nullable', 'in:'.Note::TYPE_GENERAL.','.Note::TYPE_INCIDENT],
            'employee_time_record_id' => ['nullable', 'uuid', 'exists:employee_time_records,id'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $type = $this->validated('type') ?? $this->input('type') ?? Note::TYPE_GENERAL;

                if ($type === Note::TYPE_INCIDENT && !$this->input('employee_time_record_id')) {
                    $validator->errors()->add('employee_time_record_id', 'El registro horario es obligatorio para una incidencia.');
                }
            },
        ];
    }
}
