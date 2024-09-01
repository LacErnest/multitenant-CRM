<?php

namespace App\Listeners;

use Tenancy\Hooks\Database\Events\Drivers\Deleting;

class ConfigureDeletingTenantEvent
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
     * @param Deleting $event
     * @return void
     */
    public function handle(Deleting $event)
    {
        $event->configuration['username'] = str_replace('-', null, $event->configuration['username']);
        $event->configuration['host'] = '%';
    }
}
