<?php

namespace App\Services;

use Iconify\JSONTools\Collection;
use Iconify\JSONTools\SVG;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class IconService
{
    public function listIcons(
        array $collections,
        string $q = '',
        int $limit = 100,
        int $offset = 0,
        bool $withSvg = false,
        array $svgAttrs = ['height' => '1em']
    ): array {
        $q = trim(Str::lower($q));
        $acc = [];
        $index = 0;

        foreach ($collections as $prefix) {
            foreach ($this->listFromCollection($prefix) as $name) {
                if ($q !== '' && !Str::contains(Str::lower($name), $q)) {
                    continue;
                }

                if ($index++ < $offset) {
                    continue;
                }

                $slug = "{$prefix}:{$name}";
                $row = [
                    'slug' => $slug,
                    'collection' => $prefix,
                    'name' => $name,
                ];

                if ($withSvg) {
                    $attrsKey = md5(json_encode($svgAttrs));
                    $row['svg'] = Cache::remember(
                        "icon:svg:{$slug}:{$attrsKey}",
                        86400,
                        fn () => $this->svg($slug, $svgAttrs)
                    );
                }

                $acc[] = $row;

                if (count($acc) >= $limit) {
                    return $acc;
                }
            }
        }

        return $acc;
    }


    public function svg(string $slug, array $attrs = ['height' => '1em']): ?string
    {
        if (!str_contains($slug, ':')) return null;
        [$prefix, $name] = explode(':', $slug, 2);

        $file = $this->locateCollectionFile($prefix);
        if (!$file) return null;

        $collection = new Collection();

        $cacheFile = $this->cacheFile($prefix);
        $mtime = filemtime($file);
        $loaded = $collection->loadFromCache($cacheFile, $mtime);
        if (!$loaded) {
            if (!$collection->loadFromFile($file)) return null;
            $collection->saveCache($cacheFile, $mtime);
        }

        if (!$collection->iconExists($name)) return null;
        $data = $collection->getIconData($name);

        $svg = new SVG($data);
        return $svg->getSVG($attrs);
    }

    protected function listFromCollection(string $prefix): array
    {
        return Cache::remember("icon:list:{$prefix}", 86400, function () use ($prefix) {
            $file = $this->locateCollectionFile($prefix);
            if (!$file) return [];
            $col = new Collection();
            if (!$col->loadFromFile($file)) return [];
            return $col->listIcons(true);
        });
    }

    protected function locateCollectionFile(string $prefix): ?string
    {
        $file = Collection::findIconifyCollection($prefix);
        return is_string($file) && is_file($file) ? $file : null;
    }

    protected function cacheFile(string $prefix): string
    {
        if (!is_dir(storage_path('app/icon-cache'))) {
            mkdir(storage_path('app/icon-cache'), 0755, true);
        }

        return storage_path("app/icon-cache/{$prefix}.php");
    }
}
