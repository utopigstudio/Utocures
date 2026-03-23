<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class SendDocument extends Notification
{
    public function __construct(
        private string $subject,
        private string $content,
        private $file,
        public string $fileName
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->subject)
            ->line($this->content)
            ->attachData(
                $this->file->output(),
                $this->fileName,
                ['mime' => 'application/pdf']
            );
    }
}
