<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClientAccessInvitationNotification extends Notification
{
    public function __construct(private string $name, private string $token) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Acceso al portal de clientes de Utocures')
            ->line("Hola {$this->name}, se ha creado tu acceso al portal de clientes.")
            ->line('Utiliza el siguiente enlace para establecer tu contraseña:')
            ->action('Establecer contraseña', url(route('password.reset', [
                'token' => $this->token,
                'email' => $notifiable->email,
            ], false)));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            //
        ];
    }
}
