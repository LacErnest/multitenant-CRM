<?php

namespace App\Http\Resources\Service;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class ServiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $rate = 1;
        if (UserRole::isAdmin(auth()->user()->role) && Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
            $rate = 1/getCurrencyRates()['rates']['USD'];
        }

        return [
          'id' => $this->id,
          'name' => $this->name ?? null,
          'price' => $this->resource_id ? $this->price : ($this->price ? ceiling($this->price * $rate, 2) : null),
          'price_unit' => $this->price_unit ?? null,
          'resource_id' => $this->resource_id ?? null,
          'resource' => $this->resource_id ? $this->resourceOfService->name : null
        ];
    }
}
