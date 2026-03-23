<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Inspiring;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        [$message, $author] = str(Inspiring::quotes()->random())->explode('-');

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'employee' => $request->user()?->employee !== null,
            ],
            'ziggy' => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'notification' => fn () => collect(['success', 'error', 'info'])
                    ->mapWithKeys(fn ($type) => [$type => $request->session()->get($type)])
                    ->filter()
                    ->map(fn ($message, $type) => [
                        'type' => $type,
                        'message' => $message,
                    ])
                    ->first(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'translations' => function () {
                $locale = app()->getLocale();
                $files = glob(base_path("lang/{$locale}/*.php"));
                $strings = [];

                foreach ($files as $file) {
                    $name = basename($file, '.php');
                    $strings[$name] = require $file;
                }

                return $strings;
            },
        ];
    }
}
