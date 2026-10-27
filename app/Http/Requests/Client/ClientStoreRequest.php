<?php

namespace App\Http\Requests\Client;

use App\Models\Country;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'use_billing_recipient' => ['sometimes', 'boolean'],
            'billing_recipient' => ['exclude_unless:use_billing_recipient,1', 'required', 'array:name,cif_nif,address,city,zip_code,country_id,email'],
            'billing_recipient.name' => ['exclude_unless:use_billing_recipient,1', 'required', 'string', 'max:255'],
            'billing_recipient.cif_nif' => ['exclude_unless:use_billing_recipient,1', 'required', 'string', 'max:20'],
            'billing_recipient.address' => ['exclude_unless:use_billing_recipient,1', 'required', 'string', 'max:255'],
            'billing_recipient.city' => ['exclude_unless:use_billing_recipient,1', 'required', 'string', 'max:255'],
            'billing_recipient.zip_code' => ['exclude_unless:use_billing_recipient,1', 'required', 'string', 'digits:5'],
            'billing_recipient.country_id' => ['exclude_unless:use_billing_recipient,1', 'required', 'string', Rule::in(Country::all()->pluck('id')->all())],
            'billing_recipient.email' => ['exclude_unless:use_billing_recipient,1', 'nullable', 'email', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'birth_date' => ['nullable', 'date'],
            'gender_id' => ['required', 'string', 'max:255'],
            'cif_nif' => ['required', 'string', 'max:10', Rule::unique('clients', 'cif_nif')->ignore($this->client)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('clients', 'email')->ignore($this->client)],
            'phone' => ['required', 'string', 'max:15'],
            'phone_2' => ['nullable', 'string', 'max:15'],
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'zip_code' => ['required', 'string', 'max:5'],
            'country_id' => ['required', 'string', Rule::in(Country::all()->pluck('id')->all())],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account' => ['nullable', 'string', 'max:34'],
            'tax_included' => ['required', 'boolean'],
            'is_partner' => ['required', 'boolean'],
            'automatic_invoice' => ['required', 'boolean'],
            'services' => ['nullable', 'array'],
            'services.*' => ['string', 'exists:services,id'],
            'characteristics' => ['nullable', 'array'],
            'characteristics.*' => ['string', 'exists:characteristic_options,id'],
        ];
    }
}
