<?php

namespace App\Http\Resources\ResourceInvoice;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Http\Resources\CreditNote\CreditNoteResource;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Models\Company;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ResourceInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isExternalAuth = auth()->user() instanceof Resource;
        $customerTotalPrice = 0;
        if ($this->currency_rate_customer) {
            $customerTotalPrice = $this->total_price * $this->currency_rate_customer;
        }
        return [
          'id' => $this->id,
          'resource_id' => $this->purchaseOrder->resource_id ?? null,
          'created_by' => $this->created_by,
          'xero_id' => $this->xero_id ?? null,
          'xero_entities'          => $this->legalEntity->xeroConfig,
          'project_id' => $this->project_id ?? null,
          'project' => $this->project ? $this->project->name : null,
          'order_id' => $this->order_id ?? null,
          'order' => $this->order ? $this->order->number : null,
          'type' => $this->type ?? null,
          'date' => $this->date ?? null,
          'due_date' => $this->due_date ?? null,
          'status' => $this->status ?? null,
          'pay_date' => $this->pay_date ?? null,
          'number' => $this->number ?? null,
          'reference' => $this->reference ?? null,
          'currency_code' => $this->currency_code ?? null,
          'currency_rate_customer' => $this->currency_rate_customer ?? null,
          'created_at' => $this->created_at,
          'updated_at' => $this->updated_at ?? null,
          'items' => ItemResource::collection($this->items->load('entity')->sortBy('order')),
          'price_modifiers' => ModifierResource::collection($this->priceModifiers->load('entity')->sortBy('quantity_type')),
          'total_price' => $isExternalAuth ? ($this->total_price ? ceiling($this->total_price * $this->currency_rate_customer, 2) :
              null) : (auth()->user() ? (UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              $this->total_price : $this->total_price_usd) : $this->total_price),
          'total_vat' => $isExternalAuth ? ($this->total_vat ? ceiling($this->total_vat * $this->currency_rate_customer, 2) :
              null) : (auth()->user() ? (UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              $this->total_vat : $this->total_vat_usd) : $this->total_vat),
              'customer_total_price' => ceiling($customerTotalPrice, 2),
          'download' => $this->hasMedia('invoice_uploads'),
          'tax_rate'               => getTaxRate($this->date, $this->legal_entity_id),
          'legal_entity_id'        => $this->legal_entity_id,
          'penalty' => $this->purchaseOrder->penalty ?? null,
          'penalty_type' => $this->purchaseOrder->penalty_type ?? null,
          'reason_of_penalty' => $this->purchaseOrder->reason_of_penalty ?? null,
          'details' => [
              'columns' => [
                  [
                      'prop' => 'date',
                      'name' => 'date',
                      'type' => 'date'
                  ],
                  [
                      'prop' => 'status',
                      'name' => 'status',
                      'type' => 'enum',
                      'enum' => 'creditnotestatus'
                  ],
                  [
                      'prop' => 'type',
                      'name' => 'type',
                      'type' => 'enum',
                      'enum' => 'creditnotetype'
                  ],
                  [
                      'prop' => 'number',
                      'name' => 'number',
                      'type' => 'string'
                  ],
                  [
                      'prop' => 'total_price',
                      'name' => 'total price',
                      'type' => 'decimal'
                  ],
                  [
                      'prop' => 'total_vat',
                      'name' => 'total vat',
                      'type' => 'decimal'
                  ]
              ],
              'rows' => CreditNoteResource::collection($this->creditNotes)
          ]
        ];
    }
}
