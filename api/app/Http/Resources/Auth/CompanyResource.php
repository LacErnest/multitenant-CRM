<?php

namespace App\Http\Resources\Auth;

use App\Enums\UserRole;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $xero_linked = false;
        if ($this->xero_tenant_id && $this->xero_access_token) {
            $xero_linked = true;
        }

        return [
          'id' => $this->id,
          'name' => $this->name,
          'initials' => $this->initials,
          'currency' => $this->currency_code,
          'xero_linked' =>  $xero_linked,
          'role' => UserRole::isAdmin(auth()->user()->role) ? UserRole::admin()->getIndex() : $this->whenLoaded('users', $this->users->first()->role),
          'sales_support_percentage' => $this->sales_support_commission,
        ];
    }
}
