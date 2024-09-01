<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectInvoicesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
          'id'          => $this->id,
          'order'       => $this->whenLoaded('order', fn () => $this->order->number),
          'order_id'    => $this->order_id,
          'customer_id' => $this->project->contact->customer->id,
          'customer'    => $this->project->contact->customer->name,
          'payment_due_date'    => $this->project->contact->customer->payment_due_date,
          'type'        => $this->type ?? null,
          'date'        => $this->date ?? null,
          'due_date'    => $this->due_date ?? null,
          'status'      => $this->status ?? null,
          'number'      => $this->number ?? null,
          'project_id'  => $this->project_id ?? null,
          'pay_date'    => $this->pay_date ?? null,
          'total_price' => UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2),
        ];
    }
}
