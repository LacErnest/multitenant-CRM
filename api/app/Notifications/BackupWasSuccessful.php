<?php

namespace App\Notifications;

use ThibaudDauce\Mattermost\Attachment;
use ThibaudDauce\Mattermost\Message as MattermostMessage;

class BackupWasSuccessful extends \Spatie\Backup\Notifications\Notifications\BackupWasSuccessful
{
    public function toMattermost() : MattermostMessage
    {
        return (new MattermostMessage)
          ->username($this->applicationName())
          ->channel(config('backup.notifications.mattermost.channel'))
          ->iconUrl('https://cdn.freebiesupply.com/logos/large/2x/laravel-logo-png-transparent.png')
          ->text(trans('backup::notifications.backup_successful_subject_title'))
          ->attachment(function (Attachment $attachment) {
              $attachment->fields($this->backupDestinationProperties()->toArray());
          });
    }
}
