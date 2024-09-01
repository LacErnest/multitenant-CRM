<?php

namespace App\Http\Resources\Quote;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Http\Resources\Order\OrderResource;
use App\Models\Company;
use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;
use phpDocumentor\Reflection\Project;

class QuoteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {

        $salesPersons = $this->project->salesPersons;
        $leadGens = $this->project->leadGens;

        return [
          'id'                        => $this->id,
          'xero_id'                   => $this->xero_id ?? null,
          'project_id'                => $this->project_id ?? null,
          'name'                      => $this->project->name ?? null,
          'date'                      => $this->date ?? null,
          'expiry_date'               => $this->expiry_date ?? null,
          'status'                    => $this->status,
          'reason_of_refusal'         => $this->reason_of_refusal ?? null,
          'number'                    => $this->number ?? null,
          'reference'                 => $this->reference ?? null,
          'currency_code'             => $this->currency_code ?? null,
          'created_at'                => $this->created_at,
          'updated_at'                => $this->updated_at ?? null,
          'items'                     => ItemResource::collection($this->items->load('entity')->sortBy('order')),
          'price_modifiers'           => ModifierResource::collection($this->priceModifiers->load('entity')),
          'total_price'               => $this->manual_input ? $this->manual_price : (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ? $this->total_price : $this->total_price_usd),
          'total_vat'                 => $this->manual_input ? $this->manual_vat : (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ? $this->total_vat : $this->total_vat_usd),
          'order_id'                  => $this->project->order ? $this->project->order->id : null,
          'contact_id'                => $this->project && $this->project->contact ? $this->project->contact->id : null,
          'customer_id'               => $this->project && $this->project->contact ? $this->project->contact->customer->id : null,
          'sales_person_id'           => $this->project && $salesPersons ? $salesPersons->pluck('id') : null,
          'sales_person'              => $this->project && $salesPersons ? $salesPersons->pluck('name') : null,
          'manual_input'              => $this->manual_input,
          'down_payment_type'         => $this->down_payment_type,
          'down_payment'              => $this->down_payment ?? null,
          'tax_rate'                  => empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage,
          'legal_entity_id'           => $this->legal_entity_id,
          'legal_entity'              => $this->legalEntity ? $this->legalEntity->name : null,
          'legal_country'             => $this->legalEntity ? $this->legalEntity->address->country : null,
          'has_media'                 => $this->getMedia('document_quote')->first()->file_name ?? null,
          'vat_status'                => $this->vat_status,
          'master'                    => $this->master,
          'second_sales_person_id'    => $this->project && $leadGens ? $leadGens->pluck('id') : null,
          'second_sales_person'       => $this->project && $leadGens ? $leadGens->pluck('name') : null,
          'currency_rate_customer'    => $this->currency_rate_customer,
        ];
    }
}
