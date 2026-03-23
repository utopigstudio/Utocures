<?php

namespace App\Http\Requests\ContractTemplate;

use Illuminate\Foundation\Http\FormRequest;

class ContractTemplateIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'in:name,created_at',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser name.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
