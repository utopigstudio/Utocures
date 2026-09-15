<?php

namespace App\Support;

class ChangelogCatalog
{
    /**
     * Entries for the given locale, filtered by role, most recent first.
     *
     * The JSON files are hand-maintained: new entries must be added at the
     * top of the array so index 0 is always the latest version.
     */
    public static function entries(string $locale, string $role): array
    {
        $locale = in_array($locale, ['es', 'ca'], true) ? $locale : 'es';
        $entries = json_decode(file_get_contents(resource_path("changelog/{$locale}.json")), true, 512, JSON_THROW_ON_ERROR);

        return array_values(array_filter($entries, fn (array $entry) => in_array($role, $entry['roles'], true)));
    }

    public static function latestVersion(string $locale, string $role): ?string
    {
        return self::entries($locale, $role)[0]['version'] ?? null;
    }
}
