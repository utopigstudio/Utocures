<?php

namespace App\Http\Requests\BudgetTemplate;

use Illuminate\Foundation\Http\FormRequest;

class BudgetTemplateStoreRequest extends FormRequest
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
