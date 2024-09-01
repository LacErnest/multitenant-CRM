<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectQuotesResource extends JsonResource
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
          'id' => $this->id,
          'customer_id' => $this->project->contact->customer->id,
          'customer' => $this->project->contact->customer->name,
          'date' => $this->date ?? null,
          'expiry_date' => $this->expiry_date ?? null,
          'status' => $this->status ?? null,
          'number' => $this->number ?? null,
          'total_price' => UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2),
        ];
    }
}
