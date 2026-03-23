<?php

namespace App\Http\Requests\Budget;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Budget;

class BudgetStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id'          => ['required', 'string', 'max:255'],
            'status'             => ['required', 'integer', Rule::in(Budget::getStatuses()->pluck('id')->toArray())],
            'due_date'           => ['nullable', 'date'],
            'content'            => ['required', 'string', 'min:3'],
            'lines'              => ['required', 'array', 'min:1'],
            'lines.*.concept'    => ['required', 'string', 'min:3', 'max:255'],
            'lines.*.quantity'   => ['required', 'numeric', 'min:0.01'],
            'lines.*.price'      => ['required', 'numeric', 'min:0.01'],
            'lines.*.discount'   => ['nullable', 'numeric', 'min:0'],
            'lines.*.tax_type'   => ['required', 'string', Rule::in(['21', '10', '4', '0'])],
        ];
    }
}
