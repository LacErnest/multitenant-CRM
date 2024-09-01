<?php

namespace App\Http\Resources\PurchaseOrder;

use App\Enums\InvoiceStatus;
use App\Enums\VatStatus;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class PurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = null;
        if ($this->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->resource_id);
            }
        }

        return [
          'id'                    => $this->id,
          'xero_id'               => $this->xero_id ?? null,
          'project_id'            => $this->project_id ?? null,
          'project'               => $this->project ? $this->project->name : null,
          'resource_id'           => $this->resource_id ?? null,
          'date'                  => $this->date ?? null,
          'delivery_date'         => $this->delivery_date ?? null,
          'pay_date'              => $this->pay_date ?? null,
          'status'                => $this->status ?? null,
          'number'                => $this->number ?? null,
          'reference'             => $this->reference ?? null,
          'reason_of_rejection'   => $this->reason_of_rejection ?? null,
          'reason_of_penalty'     => $this->reason_of_penalty ?? null,
          'currency_code'         => $this->currency_code ?? null,
          'created_at'            => $this->created_at ?? null,
          'updated_at'            => $this->updated_at ?? null,
          'items'                 => ItemResource::collection($this->items->load('entity')->sortBy('order')),
          'price_modifiers'       => ModifierResource::collection($this->priceModifiers->load('entity')->sortBy('quantity_type')),
          'total_price'           => $this->manual_price ?? null,
          'total_vat'             => $this->manual_vat ?? null,
          'penalty'               => ($this->penalty + 0) ?? null,
          'penalty_type'          => $this->penalty_type ?? null,
          'rating'                => $this->rating ?? null,
          'reason'                => $this->reason ?? null,
          'resource'              => $resource ? $resource->name : null,
          'resource_currency'     => $resource ? $resource->default_currency : null,
          'tax_rate'              => empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage,
          'manual_input'          => $this->manual_input,
          'resource_country'      => $resource ? $resource->country : null,
          'legal_entity_id'       => $this->legal_entity_id,
          'legal_entity'          => $this->legalEntity ? $this->legalEntity->name : null,
          'legal_country'         => $this->legalEntity ? $this->legalEntity->address->country : null,
          'payment_terms'         => $this->payment_terms,
          'vat_status'            => $this->vat_status,
          'invoice_authorised'    => $this->invoices->isNotEmpty() && $this->invoices->first()->status >= InvoiceStatus::authorised()->getIndex(),
          'created_by'            => $this->created_by ? $this->creator->name : null,
          'authorised_by'         => $this->authorised_by ? $this->authorizer->name : null,
          'processed_by'          => $this->processed_by ? $this->processor->name : null,
          'resource_non_vat_liable'   => $resource ? $resource->non_vat_liable : false,
        ];
    }
}
