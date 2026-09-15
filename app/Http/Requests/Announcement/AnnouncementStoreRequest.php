<?php

namespace App\Http\Requests\Announcement;

use App\Models\Announcement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if (! $this->has('to')) {
            $this->merge([
                'to' => Announcement::TO_EMPLOYEE,
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $announcement = $this->route('announcement');

        return [
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'to' => ['required', Rule::in(Announcement::AUDIENCES)],
            'image' => [
                $announcement?->getRawOriginal('image') ? 'nullable' : 'required',
                'image',
                'mimes:jpeg,png,jpg,webp',
                'max:2048',
            ],
        ];
    }

    public function validated($key = null, $default = null)
    {
        $data = parent::validated();

        if ($this->hasFile('image')) {
            $data['image'] = $this->file('image')->store('uploads/announcements', 'public');
        }

        return $data;
    }
}
