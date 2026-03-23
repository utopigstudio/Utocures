<?php

namespace App\Http\Requests\Service;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Service;

class ServiceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filter_search' => 'nullable|string',
            'filter_color' => 'nullable|string|in:' . implode(',', Service::getColors()->pluck('name')->toArray()),
        ];
    }
}
