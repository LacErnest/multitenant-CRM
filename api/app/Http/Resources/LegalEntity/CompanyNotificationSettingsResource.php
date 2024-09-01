<?php

namespace App\Http\Resources\LegalEntity;

use App\Models\Contact;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyNotificationSettingsResource extends JsonResource
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
          'from_address'               => $this->from_address,
          'from_name'                  => $this->from_name,
          'invoice_submitted_body'     => $this->invoice_submitted_body,
          'cc_addresses'               => $this->cc_addresses ?? [],
          'company_id'                 => $this->company_id,
          'created_at'                 => $this->created_at,
          'updated_at'                 => $this->updated_at,
        ];
    }
}
