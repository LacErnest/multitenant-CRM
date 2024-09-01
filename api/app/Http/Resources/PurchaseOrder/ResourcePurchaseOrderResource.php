<?php

namespace App\Http\Resources\PurchaseOrder;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Http\Resources\Resource\PurchaseOrderInvoiceResource;
use App\Models\Company;
use App\Models\Resource;
use App\Services\ResourceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;

class ResourcePurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        $resource = null;
        if ($this->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->resource_id);
        }
        $isExternalAuth = auth()->user() instanceof Resource;
        if ($isExternalAuth) {
            Tenancy::setTenant(Company::find($this->company_id));
        }

        return [
          'id' => $this->id ?? null,
          'xero_id' => !$isExternalAuth ? $this->xero_id : null,
          'project_id' => $this->project_id ?? null,
          'project' => !$isExternalAuth ? $this->project->name : null,
          'resource_id' => $this->resource_id ?? null,
          'resource' => $resource ? $resource->name : null,
          'date' => $this->date ?? null,
          'delivery_date' => $this->delivery_date ?? null,
          'pay_date' => !$isExternalAuth ? $this->pay_date : null,
          'status' => $this->status ?? null,
          'number' => $this->number ?? null,
          'reference' => !$isExternalAuth ? $this->reference : null,
          'currency_code' => $this->currency_code,
          'total_price' => $isExternalAuth ? $this->manual_price :
              (UserRole::isAdmin(auth()->user()->role) || $companyCurrency == CurrencyCode::EUR()->getIndex() ?
                  ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2)),
          'total_vat' => $isExternalAuth ? $this->manual_vat :
              (UserRole::isAdmin(auth()->user()->role) || $companyCurrency == CurrencyCode::EUR()->getIndex() ?
                  ceiling($this->total_vat, 2) : ceiling($this->total_vat_usd, 2)),
          'company_id' => $isExternalAuth ? $this->company_id : getTenantWithConnection(),
          'resource_non_vat_liable' => $resource ? $this->resource_non_vat_laible : false,
          'details' => [
              'columns' => [
                  [
                      'prop' => 'number',
                      'name' => 'number',
                      'type' => 'string',
                  ],
                  [
                      'prop' => 'date',
                      'name' => 'date',
                      'type' => 'date',
                  ],
                  [
                      'prop' => 'due_date',
                      'name' => 'due date',
                      'type' => 'date',
                  ],
                  [
                      'prop' => 'status',
                      'name' => 'status',
                      'type' => 'enum',
                      'enum' => 'invoicestatus',
                  ],
                  [
                      'prop' => 'pay_date',
                      'name' => 'pay date',
                      'type' => 'date',
                  ],
                  [
                      'prop' => 'reference',
                      'name' => 'reference',
                      'type' => 'string',
                  ],
                  [
                      'prop' => 'total_price',
                      'name' => 'total price',
                      'type' => 'decimal',
                  ],
              ],
              'rows' => PurchaseOrderInvoiceResource::collection($this->invoices),
          ]
        ];
    }
}
