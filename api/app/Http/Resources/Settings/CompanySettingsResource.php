<?php

namespace App\Http\Resources\Settings;

use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Crypt;

class CompanySettingsResource extends JsonResource
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
          'id'                                => $this->id,
          'company_id'                        => $this->company_id,
          'max_commission_percentage'         => floatval($this->max_commission_percentage ?: 100),
          'sales_person_commission_limit'     => $this->sales_person_commission_limit ?: 1,
          'vat_default_value'                 => floatval($this->vat_default_value ?: 0),
          'vat_max_value'                     => floatval($this->vat_max_value ?: 20),
          'project_management_default_value'  => floatval($this->project_management_default_value ?: 10),
          'project_management_max_value'      => floatval($this->project_management_max_value ?: 50),
          'special_discount_default_value'    => floatval($this->special_discount_default_value ?: 5),
          'special_discount_max_value'        => floatval($this->special_discount_max_value ?: 20),
          'director_fee_default_value'        => floatval($this->director_fee_default_value ?: 10),
          'director_fee_max_value'            => floatval($this->director_fee_max_value ?: 50),
          'transaction_fee_default_value'     => floatval($this->transaction_fee_default_value ?: 2),
          'transaction_fee_max_value'         => floatval($this->transaction_fee_max_value ?: 50),
          'from_email'                        => $this->from_email,
          'from_name'                         => $this->from_name,
          'created_at'                        => $this->created_at,
          'updated_at'                        => $this->updated_at,
          'deleted_at'                        => $this->deleted_at,
        ];
    }
}
