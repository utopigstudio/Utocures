<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SendMagicLink extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $link
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Enlace mágico de inicio de sesión')
            ->line('Haga clic en el siguiente enlace para iniciar sesión:')
            ->action('Iniciar sesión', $this->link)
            ->line('Si no has solicitado este enlace, puedes ignorar este correo.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
