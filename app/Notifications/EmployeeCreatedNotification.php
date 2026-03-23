<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmployeeCreatedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $name,
        private string $token
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
            ->subject('Bienvenido/a a la plataforma')
            ->line("Bienvenido {$this->name}, tu usuario ha sido creado correctamente.")
            ->line('Para acceder a la plataforma necesitarás pulsar sobre el siguiente enlace y establecer tu contraseña:')
            ->action('Establecer contraseña', url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->email], false)));
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
