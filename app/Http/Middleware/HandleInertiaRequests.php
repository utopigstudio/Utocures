<?php

namespace App\Http\Middleware;

use App\Models\AdminNotification;
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
            'adminNotifications' => fn () => $this->resolveAdminNotifications($request),
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

    /**
     * Resolve shared admin notifications for Inertia layouts.
     *
     * @return array<string, mixed>|null
     */
    protected function resolveAdminNotifications(Request $request): ?array
    {
        $user = $request->user();

        if (! $user || $user->employee) {
            return null;
        }

        $unreadNotifications = AdminNotification::query()
            ->where('read_at', '=', null)
            ->latest()
            ->get();

        $readNotifications = AdminNotification::query()
            ->latest('created_at')
            ->get()
            ->filter(fn (AdminNotification $notification) => $notification->read_at !== null)
            ->take(20)
            ->values();

        $notifications = $unreadNotifications->take(10)->values();

        return [
            'count' => $unreadNotifications->count(),
            'items' => $notifications
                ->map(fn (AdminNotification $notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                    'data' => $notification->data ?? [],
                ])
                ->values()
                ->all(),
            'readCount' => $readNotifications->count(),
            'readItems' => $readNotifications
                ->map(fn (AdminNotification $notification) => [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->title,
                    'message' => $notification->message,
                    'action_url' => $notification->action_url,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                    'data' => $notification->data ?? [],
                ])
                ->values()
                ->all(),
        ];
    }
}
