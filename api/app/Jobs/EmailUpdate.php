<?php

namespace App\Jobs;

use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Mail\QuoteNotApproved;
use App\Models\Contact;
use App\Models\Employee;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\User;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Redis\LimiterTimeoutException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redis;

class EmailUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tenant_key;
    protected $model;
    protected $model_id;

    /**
     * Create a new job instance.
     *
     * @param $tenant_key
     * @param string $model
     * @param $model_id
     */
    public function __construct($tenant_key, string $model, $model_id)
    {
        $this->tenant_key = $tenant_key;
        $this->model = $model;
        $this->model_id = $model_id;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws LimiterTimeoutException
     */
    public function handle()
    {
        Redis::funnel($this->tenant_key)->limit(1)->then(function () {
            if ($this->model == Quote::class) {
                $quote = Quote::with('project')->find($this->model_id);
                if ($quote) {
                    if (QuoteStatus::isSent($quote->status)) {
                        $salesPersons = $quote->project->salesPersons;
                        foreach ($salesPersons as $salesPerson) {
                            $owners = $salesPerson->company->users->where('role', UserRole::owner()->getIndex())->all();
                            Mail::to($salesPerson)->cc($owners)->queue(new QuoteNotApproved($this->tenant_key, $quote->id));
                        }
                    }
                }
            }
        }, function () {
            $this->release(5);
        });
    }
}
