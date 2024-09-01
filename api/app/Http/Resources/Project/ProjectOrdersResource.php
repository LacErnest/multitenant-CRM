<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectOrdersResource extends JsonResource
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
          'quote' => $this->quote ? $this->quote->number : null,
          'quote_id' => $this->quote ? $this->quote_id : null,
          'date' => $this->date ?? null,
          'status' => $this->status ?? null,
          'number' => $this->number ?? null,
          'total_price' => UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2),
          'shadow' => $this->shadow,
          'intra_company' => isIntraCompany($this->project->contact->customer->id ?? null),
        ];
    }
}
