<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class UserWelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param mixed $notifiable
     * @return array<int, string>
     */
    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Welcome to ' . config('app.name'))
            ->greeting('Hello ' . $notifiable->first_name . '!')
            ->line('Welcome to our platform! We\'re excited to have you on board.')
            ->line('Your account has been successfully created and you can now start using our services.')
            ->action('Get Started', url('/dashboard'))
            ->line('If you have any questions, feel free to contact our support team.')
            ->salutation('Best regards, ' . config('app.name') . ' Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param mixed $notifiable
     * @return array<string, mixed>
     */
    public function toArray($notifiable): array
    {
        return [
            'title' => 'Welcome to ' . config('app.name'),
            'message' => 'Your account has been successfully created. Welcome to our platform!',
            'type' => 'welcome',
            'action_url' => url('/dashboard'),
        ];
    }
}
