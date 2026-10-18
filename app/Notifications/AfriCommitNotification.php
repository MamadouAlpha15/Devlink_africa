<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base of every AfriCommit notification (cahier, section 14).
 * Respects the member's settings (section 23): "Plateforme" (database) and "Email".
 */
abstract class AfriCommitNotification extends Notification
{
    /** Text shown in the bell and the email: "Ibrahim Sow souhaite rejoindre AfricaDelivery". */
    abstract protected function message(): string;

    /** Page opened when the notification is clicked. */
    abstract protected function url(): string;

    /** Emoji shown next to the notification. */
    abstract protected function icon(): string;

    /** Short type stored in the database ("collaboration_requested"). */
    abstract public function databaseType(object $notifiable): string;

    /** Only the important notifications are also sent by email. */
    protected function sendsMail(): bool
    {
        return false;
    }

    /**
     * @return list<string>
     */
    public function via(User $notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_on_platform) {
            $channels[] = 'database';
        }

        if ($this->sendsMail() && $notifiable->notify_by_email && $notifiable->hasVerifiedEmail()) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * @return array{message: string, url: string, icon: string}
     */
    public function toArray(User $notifiable): array
    {
        return [
            'message' => $this->message(),
            'url' => $this->url(),
            'icon' => $this->icon(),
        ];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->icon().' '.$this->message())
            ->greeting('Bonjour '.($notifiable->first_name ?: $notifiable->name).',')
            ->line($this->message())
            ->action('Voir sur AfriCommit', $this->url())
            ->line('Vous pouvez désactiver ces emails dans les paramètres de votre profil.')
            ->salutation('L\'équipe AfriCommit');
    }
}
