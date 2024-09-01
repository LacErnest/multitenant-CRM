<?php

namespace App\Notifications;

use ThibaudDauce\Mattermost\Attachment;
use ThibaudDauce\Mattermost\Message as MattermostMessage;

class BackupHasFailed extends \Spatie\Backup\Notifications\Notifications\BackupHasFailed
{
    public function toMattermost() : MattermostMessage
    {
        return (new MattermostMessage)
          ->username($this->applicationName())
          ->channel(config('backup.notifications.mattermost.channel'))
          ->iconUrl('https://cdn.freebiesupply.com/logos/large/2x/laravel-logo-png-transparent.png')
          ->text(trans('backup::notifications.backup_failed_subject', ['application_name' => $this->applicationName()]))
          ->attachment(function (Attachment $attachment) {
              $attachment->authorName($this->applicationName())
                  ->title(trans('laravel-backup::notifications.exception_message_title'))
                  ->text($this->event->exception->getMessage());
          })
          ->attachment(function (Attachment $attachment) {
              $attachment->authorName($this->applicationName())
                  ->title(trans('laravel-backup::notifications.exception_trace_title'))
                  ->text($this->event->exception->getTraceAsString());
          })
          ->attachment(function (Attachment $attachment) {
              $attachment->fields($this->backupDestinationProperties()->toArray());
          });
    }
}
