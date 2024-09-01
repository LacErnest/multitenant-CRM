<?php

namespace App\Notifications;


class Notifiable extends \Spatie\Backup\Notifications\Notifiable
{
    public function routeNotificationForMattermost()
    {
        return config('backup.notifications.mattermost.webhook_url');
    }
}
