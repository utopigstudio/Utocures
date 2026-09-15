<?php

namespace App\Http\Middleware;

use App\Models\AdminNotification;
use App\Models\Configuration;
use App\Models\Message;
use App\Models\UserRole;
use App\Support\ChangelogCatalog;
use Closure;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    private const AVAILABLE_LOCALES = [
        'es' => 'Español',
        'ca' => 'Català',
    ];

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    public function handle(Request $request, Closure $next)
    {
        app()->setLocale($this->resolveLocale($request));

        return parent::handle($request, $next);
    }

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
        $locale = app()->getLocale();
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'locale' => $locale,
            'available_locales' => fn () => collect(self::AVAILABLE_LOCALES)
                ->map(fn (string $label, string $value) => [
                    'value' => $value,
                    'label' => $label,
                ])
                ->values()
                ->all(),
            'auth' => [
                'user' => $user,
                'role' => $user?->role,
                'employee' => $user?->employee?->only('id'),
                'client' => $user?->client?->only('id', 'name'),
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
            'changelog' => fn () => $this->resolveChangelog($request),
            'unreadMessagesCount' => fn () => $this->resolveUnreadMessagesCount($request),
            'webPush' => fn () => $this->resolveWebPushConfiguration($request),
            'app_configuration' => fn () => $this->resolveConfiguration(),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'translations' => function () use ($locale) {
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

    protected function resolveLocale(Request $request): string
    {
        $userLocale = $request->user()?->locale;

        if (is_string($userLocale) && array_key_exists($userLocale, self::AVAILABLE_LOCALES)) {
            return $userLocale;
        }

        $appLocale = config('app.locale', 'es');

        if (is_string($appLocale) && array_key_exists($appLocale, self::AVAILABLE_LOCALES)) {
            return $appLocale;
        }

        return 'es';
    }

    /**
     * Resolve shared admin notifications for Inertia layouts.
     *
     * @return array<string, mixed>|null
     */
    protected function resolveAdminNotifications(Request $request): ?array
    {
        $user = $request->user();

        if (! $user?->isAdmin()) {
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

    /**
     * @return array{latestVersion: string|null, hasUnseen: bool}|null
     */
    protected function resolveChangelog(Request $request): ?array
    {
        $user = $request->user();

        if (! $user) {
            return null;
        }

        $latestVersion = ChangelogCatalog::latestVersion(app()->getLocale(), $user->role);

        return [
            'latestVersion' => $latestVersion,
            'hasUnseen' => $latestVersion !== null && $latestVersion !== $user->last_seen_changelog_version,
        ];
    }

    protected function resolveConfiguration(): array
    {
        $configuration = Configuration::query()->first();

        return [
            'require_time_record_location' => (bool) $configuration?->require_time_record_location,
        ];
    }

    protected function resolveUnreadMessagesCount(Request $request): int
    {
        $user = $request->user();

        if ($user?->isAdmin()) {
            return Message::query()
                ->whereHas('conversation.client')
                ->where('sender_role', UserRole::CLIENT)
                ->whereNull('read_at')
                ->count();
        }

        if ($user?->isClient() && $user->client) {
            return $user->client->messages()
                ->where('sender_role', UserRole::ADMIN)
                ->whereNull('read_at')
                ->count();
        }

        return 0;
    }

    /**
     * @return array{enabled: bool, publicKey: string|null}
     */
    protected function resolveWebPushConfiguration(Request $request): array
    {
        $user = $request->user();
        $allowed = $user?->isAdmin() || $user?->isClient();
        $publicKey = config('webpush.vapid.public_key');
        $privateKey = config('webpush.vapid.private_key');
        $subject = config('webpush.vapid.subject');
        $enabled = $allowed && filled($publicKey) && filled($privateKey) && filled($subject);

        return [
            'enabled' => $enabled,
            'publicKey' => $enabled ? $publicKey : null,
        ];
    }
}
