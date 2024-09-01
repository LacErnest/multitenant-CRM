<?php

namespace App\Jobs;

use App\Enums\UserRole;
use App\Mail\MarkUpNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class MarkUpNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $tenant_key;
    protected string $orderId;
    protected string $markUp;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(string $tenant_key, string $orderId, string $markUp)
    {
        $this->tenant_key = $tenant_key;
        $this->orderId = $orderId;
        $this->markUp = $markUp;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $order = Order::find($this->orderId);

        if ($order) {
            Redis::throttle($this->orderId)->allow(1)->every(86400)->then(function () use ($order) {
                $mailTo = [];
                $owners = User::where('company_id', $this->tenant_key)->where('role', UserRole::owner()->getIndex())->get();
                foreach ($owners as $owner) {
                    array_push($mailTo, $owner->email);
                }
                Mail::to($mailTo)
                ->queue(new MarkUpNotification($this->tenant_key, $order->project_id, $order->id, $this->markUp, $order->number));
            }, function () {
                $this->delete();
            });
        }
    }
}
