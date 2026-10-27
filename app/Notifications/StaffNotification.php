<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** In-app notification only (database channel). No email is sent. */
class StaffNotification extends Notification
{
    public function __construct(private string $message, private string $url, private string $kind)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => $this->message, 'url' => $this->url, 'kind' => $this->kind];
    }
}
