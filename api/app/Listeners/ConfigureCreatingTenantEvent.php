<?php

namespace App\Listeners;

use Tenancy\Hooks\Database\Events\Drivers\Creating;

class ConfigureCreatingTenantEvent
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
     * @param Creating $event
     * @return void
     */
    public function handle(Creating $event)
    {
        $event->configuration['username'] = str_replace('-', null, $event->configuration['username']);
        $event->configuration['host'] = '%';
    }
}
