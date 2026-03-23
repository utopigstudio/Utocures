<?php

namespace App\Http\Requests\Contract;

use App\Models\Contract;
use Illuminate\Validation\Rule;
use Illuminate\Foundation\Http\FormRequest;

class ContractIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'in:client_name,status,subtotal,discount,total,created_at',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string',
            'filter_status' => ['nullable', Rule::in(array_keys(Contract::STATUSES))],
            'filter_year' => 'nullable|integer|max:' . date('Y'),
            'export' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser client_name, status, created_at, subtotal, discount o total.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
