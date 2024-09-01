<?php

namespace App\Http\Resources\Employee;

use Illuminate\Http\Resources\Json\JsonResource;
use function Complex\theta;

class EmployeeProjectResource extends JsonResource
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
          'project_id'        => $this->project_id,
          'order_id'          => $this->project->order->id,
          'order'             => $this->project->order->number,
          'hours'             => $this->hours,
          'costs'             => $this->costs,
          'total_price'       => $this->project->order->total_price,
          'total_price_usd'   => $this->project->order->total_price_usd,
          'customer_id'       => $this->project->contact->customer->id,
          'customer'          => $this->project->contact->customer->name,
        ];
    }
}
