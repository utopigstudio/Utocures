<?php

namespace App\Models;

use Illuminate\Support\Collection;

class Country
{
    public CONST OPTIONS = [
        'es' => 'España',
    ];

    public static function find(string|null $key): ?object
    {
        if (!array_key_exists($key, self::OPTIONS)) {
            return null;
        }

        return (object) ['id' => $key, 'name' => self::OPTIONS[$key]];
    }

    public static function all(): Collection
    {
        $items = array_map(fn($key, $value) => (object)['id' => $key, 'name' => $value], array_keys(self::OPTIONS), self::OPTIONS);
        return collect($items);
    }
}