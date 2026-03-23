<?php

namespace App\Http\Requests\Configuration;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;

class ConfigurationStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $configuration = $this->route('configuration');

        return [
            'company_name'        => ['required', 'string', 'max:255'],
            'company_cif_nif'     => ['nullable', 'string', 'max:10'],
            'company_email'       => ['required', 'string', 'email', 'max:255'],
            'company_phone'       => ['required', 'string', 'max:15'],
            'company_address'     => ['required', 'string', 'max:255'],
            'company_city'        => ['required', 'string', 'max:255'],
            'company_zip_code'    => ['required', 'string', 'max:5'],
            'company_country_id'  => ['required', 'string', Rule::in(Country::all()->pluck('id')->toArray())],
            'company_image' => [
                $configuration?->company_image ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg',
                'max:2048',
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if ($this->hasFile('company_image')) {
            $data['company_image'] = $this->file('company_image')->store('uploads/company_images', 'public');
        }

        return $data;
    }
}
