<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;

class ClientIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|in:10,15,25,50,100',
            'sort' => 'in:cif_nif,name,email,phone,address,city,zip_code,created_at',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string',
            'filter_is_partner' => 'nullable|boolean',
            'export' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'sort.in' => 'El campo sort debe ser cif_nif, name, email, phone, address, city o zip_code.',
            'dir.in' => 'El campo dir debe ser asc o desc.',
        ];
    }
}
