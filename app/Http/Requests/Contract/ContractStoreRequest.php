<?php

namespace App\Http\Requests\Contract;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Contract;

class ContractStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'              => ['required', 'string', 'max:255'],
            'client_id'          => ['required', 'string', 'max:255', Rule::exists('clients', 'id')],
            'status'             => ['required', 'integer', Rule::in(Contract::getStatuses()->pluck('id')->toArray())],
            'date_start'         => ['required', 'date'],
            'date_end'           => ['nullable', 'date', 'after_or_equal:date_start'],
            'content'            => ['required', 'string', 'min:3'],
        ];
    }
}
