<?php

namespace App\Http\Requests\Invoice;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'          => ['required', 'string', 'exists:clients,id'],
            'date'               => ['required', 'date'],
            'due_date'           => ['date', 'after_or_equal:date'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.concept'    => ['required', 'string', 'min:3', 'max:255'],
            'lines.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'lines.*.price'      => ['required', 'numeric', 'min:0.01'],
            'lines.*.discount'   => ['nullable', 'numeric', 'min:0.00'],
            'lines.*.tax_type'   => ['required', 'string', Rule::in(['21', '10', '4', '0'])],
        ];
    }
}
