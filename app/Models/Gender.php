<?php

namespace App\Models;

use Illuminate\Support\Collection;

class Gender
{
    public CONST OPTIONS = ['Male', 'Female', 'Other'];

    public static function find(string|null $key): ?object
    {
        $option = self::OPTIONS[$key] ?? null;

        if (!$option) {
            return null;
        }

        return (object) ['id' => $key, 'name' => self::OPTIONS[$key]];
    }

    public static function all(): Collection
    {
        return collect(self::OPTIONS)->map(fn($item, $key) => (object)['id' => $key, 'name' => $item]);
    }
}