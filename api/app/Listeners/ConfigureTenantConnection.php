<?php

namespace App\Listeners;

use Illuminate\Support\Facades\DB;
use Tenancy\Affects\Connections\Events\Drivers\Configuring;

class ConfigureTenantConnection
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
     * @param Configuring $event
     * @return void
     */
    public function handle(Configuring $event)
    {
        $config = $event->defaults($event->tenant);
        $event->useConnection('mysql', $config);
    }
}
