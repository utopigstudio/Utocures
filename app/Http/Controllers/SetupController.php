<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\User;
use App\Models\Configuration;
use App\Models\Country;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use OwenIt\Auditing\Events\AuditCustom;
use Illuminate\Support\Facades\Event;

class SetupController extends Controller
{
    public function index()
    {
        $countries = Country::all();

        return Inertia::render('setup/Form', [
            'existsUser' => User::exists(),
            'existsConfig' => Configuration::exists(),
            'countries' => $countries,
        ]);
    }

    public function store(Request $request)
    {
        $existsUser = User::exists();
        $existsConfig = Configuration::exists();

        $rules = $this->createRules($existsUser, $existsConfig);
        $data = $request->validate($rules);

        if (!$existsUser) {
            User::withoutAuditing(function () use ($data) {
                User::create([
                    'name' => $data['user_name'],
                    'email' => $data['user_email'],
                    'password' => Hash::make($data['user_password']),
                ]);
            });
        }

        if (!$existsConfig) {
            Configuration::withoutAuditing(function () use ($data) {
                Configuration::create([
                    'company_name' => $data['company_name'],
                    'company_cif_nif' => $data['company_cif_nif'],
                    'company_email' => $data['company_email'],
                    'company_phone' => $data['company_phone'],
                    'company_address' => $data['company_address'],
                    'company_city' => $data['company_city'],
                    'company_zip_code' => $data['company_zip_code'],
                    'company_country_id' => $data['company_country_id'],
                    'company_image' => $data['company_image']->store('uploads/company_images', 'public'),
                ]);
            });
        }

        $this->createAudit();

        return redirect()->route('home')->with('success', 'Configuración guardada con éxito.');
    }

    private function createRules($existsUser, $existsConfig)
    {
        $rules = [];
        $countryIds = Country::all()->pluck('id')->all();

        if (!$existsUser) {
            $rules = array_merge($rules, [
                'user_name' => 'required|string|max:255',
                'user_email' => 'required|email|max:255|unique:users,email',
                'user_password' => 'required|string|min:8|confirmed',
                'user_password_confirmation' => 'required|string|min:8',
            ]);
        } else {
            $rules = array_merge($rules, [
                'user_name' => 'nullable|string|max:255',
                'user_email' => 'nullable|email|max:255|unique:users,email',
                'user_password' => 'nullable|string|min:8|confirmed',
                'user_password_confirmation' => 'nullable|string|min:8',
            ]);
        }

        if (!$existsConfig) {
            $rules = array_merge($rules, [
                'company_name' => 'required|string|max:255',
                'company_cif_nif' => 'required|string|max:20',
                'company_email' => 'required|email|max:255',
                'company_phone' => 'required|string|max:15',
                'company_address' => 'required|string|max:500',
                'company_city' => 'required|string|max:255',
                'company_zip_code' => 'required|string|max:20',
                'company_country_id' => ['required', Rule::in($countryIds)],
                'company_image' => 'required|image|max:2048',
            ]);
        } else {
            $rules = array_merge($rules, [
                'company_name' => 'nullable|string|max:255',
                'company_cif_nif' => 'nullable|string|max:20',
                'company_email' => 'nullable|email|max:255',
                'company_phone' => 'nullable|string|max:15',
                'company_address' => 'nullable|string|max:500',
                'company_city' => 'nullable|string|max:255',
                'company_zip_code' => 'nullable|string|max:20',
                'company_country_id' => ['nullable', Rule::in($countryIds)],
                'company_image' => 'nullable|image|max:2048',
            ]);
        }

        return $rules;
    }

    private function createAudit()
    {
        $audit = Configuration::first();
        $audit->auditEvent = __('setup.completed');
        $audit->isCustomEvent = true;
        $audit->auditCustomOld = [];
        $audit->auditCustomNew = [];
        Event::dispatch(new AuditCustom($audit));
    }
}
