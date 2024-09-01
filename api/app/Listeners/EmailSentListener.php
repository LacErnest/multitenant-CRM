<?php

namespace App\Listeners;

use App\Events\InvoiceSubmittedSuccessfully;
use App\Listeners\EmailSentFactory\EmailSentBuilder;
use App\Listeners\EmailSentFactory\InvoiceSubmittedFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EmailSentListener
{
    /**
     * @var string
     */
    protected $tenant;
    /**
     * @var array
     */
    protected $events = [
        InvoiceSubmittedSuccessfully::class => [
            InvoiceSubmittedFactory::class
        ]
    ];
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
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        $tenantHeader = $event->message->getHeaders()->get('X-Tenant-Id');
        $eventHeader = $event->message->getHeaders()->get('X-Event');
        if ($tenantHeader && $eventHeader) {
            $this->tenant = $tenantHeader->getFieldBody() ?? null;
            $targetEvent  = $eventHeader->getFieldBody() ?? null;
            if (!empty($targetEvent) && array_key_exists($targetEvent, $this->events)) {
                foreach ($this->events[$targetEvent] as $factory) {
                    if (in_array(EmailSentBuilder::class, class_implements($factory))) {
                        $factoryInstance = new $factory();
                        event($factoryInstance->event($this->tenant, $event));
                    }
                }
            }
        }
    }
}
