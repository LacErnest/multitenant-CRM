<?php

namespace App\Listeners;

use Tenancy\Affects\Cache\Events\ConfigureCache;

class ConfigureTenantCache
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
     * @param ConfigureCache $event
     * @return void
     */
    public function handle(ConfigureCache $event)
    {
        $event->config = [
          'driver' => 'database',
          'table' => 'cache',
          'connection' => 'tenant'
        ];
    }
}
