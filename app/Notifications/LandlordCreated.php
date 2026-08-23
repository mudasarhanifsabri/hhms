<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandlordCreated extends Notification
{
    use Queueable;

    public function __construct(
        private readonly User $landlord,
        private readonly string $temporaryPassword,
    )
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Hello ' . $this->landlord->name . ',')
            ->line('Your owner account has been created successfully.')
            ->line('Username: ' . $this->landlord->email)
            ->line('Temporary password: ' . $this->temporaryPassword)
            ->line('For your security, please change this temporary password after your first login.')
            ->action('Open Owner Mobile App', route('landlord.app'))
            ->line('Desktop Owner Portal: ' . route('landlord.dashboard'))
            ->line('Thank you.');
    }
}
