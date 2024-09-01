<?php

namespace App\Http\Resources\Resource;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Resource;
use Illuminate\Http\Resources\Json\JsonResource;

class PurchaseOrderInvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isExternalAuth = auth()->user() instanceof Resource;

        return [
          'id'                     => $this->id,
          'project_id'             => !$isExternalAuth ? $this->project_id : null,
          'project'                => !$isExternalAuth && $this->project ? $this->project->name : null,
          'order_id'               => !$isExternalAuth ? $this->order_id : null,
          'order'                  => !$isExternalAuth && $this->order ? $this->order->number : null,
          'date'                   => $this->date ?? null,
          'due_date'               => $this->due_date ?? null,
          'status'                 => $this->status ?? null,
          'pay_date'               => $this->pay_date ?? null,
          'number'                 => $this->number ?? null,
          'reference'              => $this->reference ?? null,
          'currency_code'          => $this->currency_code ?? null,
          'total_price'            => $isExternalAuth ? $this->manual_input ? $this->manual_price : ($this->total_price ?
              ceiling($this->total_price * $this->currency_rate_customer, 2) : null) :
              (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
                  $this->total_price : $this->total_price_usd),
          'total_vat'              => $isExternalAuth ? $this->manual_input ? $this->manual_vat : ($this->total_vat ?
              round($this->total_vat * $this->currency_rate_customer, 2) : null) :
              (UserRole::isAdmin(auth()->user()->role) || Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
                  $this->total_vat : $this->total_vat_usd),
          'download'               => $this->hasMedia('invoice_uploads'),
          'purchase_order_id'      => $this->purchase_order_id,
        ];
    }
}
