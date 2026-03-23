<?php

namespace App\Http\Requests\Characteristic;

use Illuminate\Foundation\Http\FormRequest;

class CharacteristicStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                      => ['required', 'string', 'max:255'],
            'options'                   => ['nullable', 'array'],
            'options.*.id'              => ['nullable', 'string', 'exists:characteristic_options,id'],
            'options.*.name'            => ['required', 'string', 'max:255'],
        ];
    }
}
