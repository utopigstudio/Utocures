<?php

namespace App\Notifications;

use App\Models\Client;
use App\Models\Message;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

class NewMessagePushNotification extends Notification
{
    public function __construct(
        private readonly Message $message,
        private readonly Client $client,
    ) {}

    /**
     * @return array<int, class-string>
     */
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(User $notifiable): WebPushMessage
    {
        $locale = in_array($notifiable->locale, ['es', 'ca'], true)
            ? $notifiable->locale
            : config('app.locale', 'es');
        $isAdmin = $notifiable->isAdmin();
        $title = $isAdmin
            ? trans('messages.push_title_admin', ['name' => $this->client->name], $locale)
            : trans('messages.push_title_client', [], $locale);
        $url = $isAdmin
            ? route('messages.show', $this->client, false)
            : route('client.messages.index', absolute: false);

        return (new WebPushMessage)
            ->title($title)
            ->body(Str::limit(Str::squish($this->message->body), 120))
            ->icon('/pwa-icon-192.png')
            ->badge('/pwa-icon-192.png')
            ->tag("messages-{$this->client->id}")
            ->renotify()
            ->vibrate([100, 50, 100])
            ->data([
                'url' => $url,
                'client_id' => $this->client->id,
                'conversation_id' => $this->message->conversation_id,
            ])
            ->options([
                'TTL' => 3600,
                'urgency' => 'high',
                'topic' => substr(hash('sha256', "messages-{$this->client->id}"), 0, 32),
            ]);
    }
}
