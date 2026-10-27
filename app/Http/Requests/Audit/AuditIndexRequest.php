<?php

namespace App\Http\Requests\Audit;

use Illuminate\Foundation\Http\FormRequest;

class AuditIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|in:10,15,25,50,100',
            'sort' => 'in:user_id,original,changes,created_at',
            'dir' => 'in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser user_id, original o changes.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
