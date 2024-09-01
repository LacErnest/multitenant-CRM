<?php

namespace App\Listeners;

use Tenancy\Hooks\Database\Events\Drivers\Updating;

class ConfigureUpdatingTenantEvent
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param Updating $event
     * @return void
     */
    public function handle(Updating $event)
    {
        $event->configuration['username'] = str_replace('-', null, $event->configuration['username']);
        $event->configuration['host'] = '%';
    }
}
