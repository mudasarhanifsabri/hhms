<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LandlordCreated extends Notification
{
    use Queueable;

    public function __construct(private readonly User $landlord)
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
            ->line('You can use your registered email address to access the owner portal once login details are shared by the admin.')
            ->line('Thank you.');
    }
}
