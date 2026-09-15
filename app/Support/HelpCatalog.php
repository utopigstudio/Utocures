<?php

namespace App\Support;

class HelpCatalog
{
    public static function articles(string $locale, string $role): array
    {
        $locale = in_array($locale, ['es', 'ca'], true) ? $locale : 'es';
        $articles = json_decode(file_get_contents(resource_path("help/{$locale}.json")), true, 512, JSON_THROW_ON_ERROR);

        return array_values(array_filter($articles, fn (array $article) => in_array($role, $article['roles'], true)));
    }
}
