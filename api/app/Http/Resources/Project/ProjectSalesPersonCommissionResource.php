<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ProjectEmployee;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSalesPersonCommissionResource extends JsonResource
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
            'project_id' => $this['project_id'],
            'invoice_status' => $this['invoice_status'],
            'invoice_id' => $this['invoice_id'],
            'invoice' => $this['invoice'],
            'order_status' => $this['order_status'],
            'order_id' => $this['order_id'],
            'order' => $this['order'],
            'gross_margin' => $this['gross_margin'],
            'commission_percentage' => $this['commission_percentage'],
            'commission_percentage_id' => $this['commission_percentage_id'],
            'commission' => $this['commission'],
            'total_price' => $this['total_price'],
            'total_paid_amount' => $this['total_paid_amount'],
            'paid_value' => $this['paid_value'],
            'paid_at' => $this['paid_at'],
            'status' => $this['status'],
        ];
    }
}
