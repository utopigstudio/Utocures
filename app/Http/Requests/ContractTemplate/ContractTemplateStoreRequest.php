<?php

namespace App\Http\Requests\ContractTemplate;

use Illuminate\Foundation\Http\FormRequest;

class ContractTemplateStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'content'            => ['required', 'string'],
        ];
    }
}
