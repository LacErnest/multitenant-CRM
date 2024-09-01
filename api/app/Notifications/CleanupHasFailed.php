<?php

namespace App\Notifications;

use ThibaudDauce\Mattermost\Attachment;
use ThibaudDauce\Mattermost\Message as MattermostMessage;

class CleanupHasFailed extends \Spatie\Backup\Notifications\Notifications\CleanupHasFailed
{
    public function toMattermost() : MattermostMessage
    {
        return (new MattermostMessage)
          ->username($this->applicationName())
          ->channel(config('backup.notifications.mattermost.channel'))
          ->iconUrl('https://cdn.freebiesupply.com/logos/large/2x/laravel-logo-png-transparent.png')
          ->text(trans('backup::notifications.cleanup_failed_subject', ['application_name', $this->applicationName()]))
          ->attachment(function (Attachment $attachment) {
              $attachment->authorName($this->applicationName())
                  ->title(trans('laravel-backup::notifications.exception_message_title'))
                  ->text($this->event->exception->getMessage());
          })
          ->attachment(function (Attachment $attachment) {
              $attachment->authorName($this->applicationName())
                  ->title(trans('laravel-backup::notifications.exception_message_trace'))
                  ->text($this->event->exception->getTraceAsString());
          })
          ->attachment(function (Attachment $attachment) {
              $attachment->fields($this->backupDestinationProperties()->toArray());
          });
    }
}
