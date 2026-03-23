<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'in:name,email',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string|max:255',
            'filter_is_active' => 'nullable|in:0,1',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser name o email.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
