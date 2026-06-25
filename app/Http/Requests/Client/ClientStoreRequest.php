<?php

namespace App\Http\Requests\Client;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;

class ClientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'               => ['required', 'string', 'max:255'],
            'birth_date'         => ['nullable', 'date'],
            'gender_id'          => ['required', 'string', 'max:255'],
            'cif_nif'            => ['required', 'string', 'max:10', Rule::unique('clients', 'cif_nif')->ignore($this->client)],
            'email'              => ['required', 'string', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($this->client)],
            'phone'              => ['required', 'string', 'max:15'],
            'phone_2'            => ['nullable', 'string', 'max:15'],
            'address'            => ['required', 'string', 'max:255'],
            'city'               => ['required', 'string', 'max:255'],
            'zip_code'           => ['required', 'string', 'max:5'],
            'country_id'         => ['required', 'string', Rule::in(Country::all()->pluck('id')->all())],
            'bank_name'          => ['nullable', 'string', 'max:255'],
            'bank_account'       => ['nullable', 'string', 'max:34'],
            'tax_included'       => ['required', 'boolean'],
            'is_partner'         => ['required', 'boolean'],
            'automatic_invoice'  => ['required', 'boolean'],
            'services'           => ['nullable', 'array'],
            'services.*'         => ['string', 'exists:services,id'],
            'characteristics'    => ['nullable', 'array'],
            'characteristics.*'  => ['string', 'exists:characteristic_options,id'],
        ];
    }
}
