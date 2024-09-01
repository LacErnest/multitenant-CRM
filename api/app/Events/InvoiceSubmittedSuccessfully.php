<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InvoiceSubmittedSuccessfully
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @var string
     */
    protected $tenant;
    /**
     * @var string
     */
    protected $invoiceId;
    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(string $tenant, string $invoiceId)
    {
        $this->tenant = $tenant;
        $this->invoiceId = $invoiceId;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }

    public function getTenant(): string
    {
        return $this->tenant;
    }

    public function getInvoiceId()
    {
        return $this->invoiceId;
    }
}
