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
            'sort' => 'in:user_id,original,changes,created_at',
            'dir'  => 'in:asc,desc',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser user_id, original o changes.',
            'dir.in'  => 'El campo dir debe ser asc o desc.',
        ];
    }
}
