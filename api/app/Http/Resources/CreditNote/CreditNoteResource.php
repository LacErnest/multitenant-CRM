<?php

namespace App\Http\Resources\CreditNote;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Resource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CreditNoteResource extends JsonResource
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
        $company = Company::find(getTenantWithConnection());
        $isEuroCurrency = $company->currency_code == CurrencyCode::EUR()->getIndex();

        return [
          'id' => $this->id,
          'date' => $this->date ?? null,
          'status' => $this->status ?? null,
          'type' => $this->type ?? null,
          'number' => $this->number ?? null,
          'total_price' => $isExternalAuth ? ($isEuroCurrency ? $this->total_price : $this->total_price_usd) : (UserRole::isAdmin(auth()->user()->role) || $isEuroCurrency ? $this->total_price : $this->total_price_usd),
          'total_vat' => $isExternalAuth ? ($isEuroCurrency ? $this->total_vat : $this->total_vat_usd) : (UserRole::isAdmin(auth()->user()->role) || $isEuroCurrency ? $this->total_vat : $this->total_vat_usd),
        ];
    }
}
