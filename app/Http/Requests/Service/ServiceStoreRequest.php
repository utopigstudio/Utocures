<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;

class ServiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'             => ['required', 'string', 'max:255'],
            'icon_slug'        => ['required', 'string'],
            'color'            => ['required', 'string', 'max:7'],
            'description'      => ['required', 'string'],
            'price'            => ['required', 'numeric'],
            'discount_partner' => ['nullable', 'numeric'],
            'tasks'            => ['nullable', 'array'],
            'tasks.*.id'       => ['nullable', 'uuid', 'exists:service_tasks,id'],
            'tasks.*.name'     => ['required', 'string', 'max:255'],
        ];
    }
}
