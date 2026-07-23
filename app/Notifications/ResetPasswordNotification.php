<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;


        use Queueable;

        public $token;

        public function __construct($token)
        {
            $this->token = $token;
        }

        public function via($notifiable)
        {
            return ['mail'];
        }



        public function toMail($notifiable)
    {
        $url = url(route('password.reset', ['token' => $this->token, 'email' => $notifiable->email], false));

        return (new MailMessage)
            ->subject('Reset Your Password')
            ->view('emails.reset_password', [
                'url' => $url,
                'notifiable' => $notifiable, // Pass the user details
            ]);
    }
    }

