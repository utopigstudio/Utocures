<?php

namespace App\Http\Requests\Announcement;

use Illuminate\Foundation\Http\FormRequest;

class AnnouncementStoreRequest extends FormRequest
{
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