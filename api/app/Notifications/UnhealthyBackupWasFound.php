<?php

namespace App\Notifications;

use ThibaudDauce\Mattermost\Attachment;
use ThibaudDauce\Mattermost\Message as MattermostMessage;

class UnhealthyBackupWasFound extends \Spatie\Backup\Notifications\Notifications\UnhealthyBackupWasFound
{
    public function toMattermost() : MattermostMessage
    {
        return (new MattermostMessage)
          ->username($this->applicationName())
          ->channel(config('backup.notifications.mattermost.channel'))
          ->iconUrl('https://cdn.freebiesupply.com/logos/large/2x/laravel-logo-png-transparent.png')
          ->text(trans('backup::notifications.unhealthy_backup_found_subject_title', ['application_name' => $this->applicationName(), 'problem' => $this->problemDescription()]))
          ->attachment(function (Attachment $attachment) {
              $attachment->fields($this->backupDestinationProperties()->toArray());
          });
    }
}
