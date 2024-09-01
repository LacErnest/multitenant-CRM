<?php

namespace App\Notifications;

use Carbon\Carbon;
use ThibaudDauce\Mattermost\Attachment;
use ThibaudDauce\Mattermost\Message as MattermostMessage;

class MonitorCheckFailed extends \Spatie\ServerMonitor\Notifications\Notifications\CheckFailed
{
    public function toMattermost() : MattermostMessage
    {
        return (new MattermostMessage)
          ->username(config('app.name'))
          ->channel(config('backup.notifications.mattermost.channel'))
          ->iconUrl('https://cdn.freebiesupply.com/logos/large/2x/laravel-logo-png-transparent.png')
          ->text($this->getMessageText());
    }
}
