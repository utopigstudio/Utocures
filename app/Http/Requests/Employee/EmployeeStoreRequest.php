<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Country;

class EmployeeStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'              => ['required', 'string', 'max:255'],
            'nif'               => ['required', 'string', 'max:10', Rule::unique('employees', 'nif')->ignore($this->employee)],
            'birth_date'        => ['nullable', 'date'],
            'gender_id'         => ['required', 'string', 'max:255'],
            'hire_date'         => ['nullable', 'date'],
            'email'             => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->employee?->user)],
            'phone'             => ['required', 'string', 'max:15'],
            'phone_2'           => ['nullable', 'string', 'max:15'],
            'address'           => ['required', 'string', 'max:255'],
            'city'              => ['required', 'string', 'max:255'],
            'zip_code'          => ['required', 'string', 'max:5'],
            'country_id'        => ['required', 'string', Rule::in(Country::all()->pluck('id')->all())],
            'services'          => ['nullable', 'array'],
            'services.*'        => ['string', 'exists:services,id'],
            'is_active'         => ['required', 'boolean'],
            'characteristics'   => ['nullable', 'array'],
            'characteristics.*' => ['string', 'exists:characteristic_options,id'],
            'avatar'            => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if ($this->hasFile('avatar')) {
            $data['avatar'] = $this->file('avatar')->store('uploads/avatars', 'public');
        }

        return $data;
    }
}
