<?php

namespace App\Notifications\Auth;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordSetNotification extends Notification
{
    use Queueable;

    private $token;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $company = Company::find($notifiable->company_id);
        $sender = $this->getSenderParameters($company->notificationSetting);
        $url = config('app.front_url') . '/auth/set/' . $this->token . '/' . $notifiable->email;
        return (new MailMessage)
            ->subject('Activate Account')
            ->from($sender->email, $sender->name)
            ->markdown('emails.password_set_mail', [
                'user' => $notifiable,
                'url' => $url,
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }

    private function getSenderParameters($notificationSetting)
    {
        $settings = [
            'email' => $notificationSetting->from_address ?? env('MAIL_FROM_ADDRESS'),
            'name' => $notificationSetting->from_name ?? env('MAIL_FROM_NAME')
        ];

        return json_decode(json_encode($settings), false);
    }
}
