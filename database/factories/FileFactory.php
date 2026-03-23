<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Storage;

class FileFactory extends Factory
{
    public function definition(): array
    {
        $sample = collect(glob(database_path('seeders/files/*')))->random();
        $filename = basename($sample);
        $path = 'uploads/' . uniqid() . '_' . $filename;

        Storage::disk('public')->put($path, file_get_contents($sample));

        return [
            'name' => $filename,
            'path' => $path,
        ];
    }
}
