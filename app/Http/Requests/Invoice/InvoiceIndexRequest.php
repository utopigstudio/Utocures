<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'in:subtotal,discount,total,created_at,date',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string',
            'filter_date' => 'nullable|string',
            'filter_year' => 'nullable|integer|max:' . date('Y'),
            'export' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser subtotal, discount o total.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
