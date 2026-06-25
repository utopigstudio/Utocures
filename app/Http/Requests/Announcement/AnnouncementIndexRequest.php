<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sort' => 'in:title,created_at',
            'dir' => 'in:asc,desc',
            'filter_search' => 'nullable|string',
        ];
    }
}