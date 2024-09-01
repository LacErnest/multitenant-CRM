<?php

namespace App\Jobs;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Resource;
use App\Services\Xero\XeroService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;


class XeroUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tenant_key;
    public string $entity;
    public string $company;
    public string $legalEntityId;

    protected string $action;
    protected string $modelName;

    private LegalEntityRepositoryInterface $legalEntityRepository;

    /**
     * Create a new job instance.
     *
     * @param $tenant_id
     * @param string $action
     * @param string $modelName
     * @param string $entityId
     * @param string $legalEntityId
     */
    public function __construct(
        $tenant_id,
        string $action,
        string $modelName,
        string $entityId,
        string $legalEntityId
    ) {
        $this->tenant_key = $tenant_id;
        $this->modelName = $modelName;
        $this->entity = $entityId;
        $this->action = $action;
        $this->legalEntityId = $legalEntityId;
        $this->legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $company = Company::findOrFail($this->tenant_key);
        $entity = $this->modelName::findOrFail($this->entity);
        $legalEntity = $this->legalEntityRepository->firstByIdOrNull($this->legalEntityId);

        /**
         * Update invoices
         */
        if ($this->modelName == Invoice::class) {
            if ($this->action == 'created') {
                (new XeroService($legalEntity))->createInvoice($entity, $company->id);
            }

            if ($this->action == 'updated') {
                (new XeroService($legalEntity))->updateInvoice($entity, $company->id);
            }
        }
      /**
       * Update quotes
       */
        if ($this->modelName == Quote::class) {
            if ($this->action == 'created') {
                (new XeroService($legalEntity))->createQuote($entity, $company->id);
            }

            if ($this->action == 'updated') {
                (new XeroService($legalEntity))->updateQuote($entity, $company->id);
            }
        }

      /**
       * Update Customer as contact in xero
       */
        if ($this->modelName == Customer::class) {
            if ($this->action == 'created') {
                (new XeroService($legalEntity))->createCustomer($entity);
            }

            if ($this->action == 'updated') {
                (new XeroService($legalEntity))->updateCustomer($entity);
            }

            if ($this->action == 'deleted') {
                (new XeroService($legalEntity))->deleteCustomer($entity['xero_id']);
            }
        }

      /**
       * Update Resources as contact in xero
       */
        if ($this->modelName == Resource::class) {
            if ($this->action == 'created') {
                (new XeroService($legalEntity))->createResource($entity, $company->id);
            }

            if ($this->action == 'updated') {
                (new XeroService($legalEntity))->updateResource($entity, $company->id, $legalEntity->id);
            }

            if ($this->action == 'deleted') {
                (new XeroService($legalEntity))->deleteResource($entity['xero_id'], $company->id);
            }
        }
      /**
       * Update Purchase Order
       */
        if ($this->modelName == PurchaseOrder::class) {
            if ($this->action == 'created') {
                (new XeroService($legalEntity))->createPurchaseOrder($entity, $company->id);
            }

            if ($this->action == 'updated') {
                (new XeroService($legalEntity))->updatePurchaseOrder($entity, $company->id);
            }
        }
    }
}
