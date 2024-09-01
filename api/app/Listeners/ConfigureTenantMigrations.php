<?php

namespace App\Listeners;

use Tenancy\Hooks\Migration\Events\ConfigureMigrations;
use Tenancy\Tenant\Events\Deleted;

class ConfigureTenantMigrations
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
     * @param ConfigureMigrations $event
     * @return void
     */
    public function handle(ConfigureMigrations $event)
    {
        if ($event->event->tenant) {
            if ($event->event instanceof Deleted) {
                $event->disable();
            } else {
                $event->path(database_path('migrations/tenant'));
            }
        }
    }
}
