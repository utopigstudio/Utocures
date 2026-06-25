<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserCreatedNotification extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        private string $name,
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
            ->subject('Bienvenido/a a la plataforma')
            ->line("Bienvenido {$this->name}, tu usuario ha sido creado correctamente.")
            ->line('Para facilitarte el acceso ya que eres nuevo, haz clic en el siguiente enlace para iniciar sesión:')
            ->action('Iniciar sesión', $this->link)
            ->line('Una vez iniciada la sesión, podrá establecer su contraseña desde la configuración de su cuenta.')
            ->line('Para ello, puedes ir a Configuración > Usuarios.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [];
    }
}
