<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class ResourceInvoicesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = null;
        if ($this->purchaseOrder && $this->purchaseOrder->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->purchaseOrder->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->purchaseOrder->resource_id);
            }
        }

        return [
          'id'                => $this->id,
          'download'          => $this->hasMedia('invoice_uploads'),
          'order'             => $this->order ? $this->order->number : null,
          'order_id'          => $this->order ? $this->order_id : null,
          'type'              => $this->type ?? null,
          'date'              => $this->date ?? null,
          'due_date'          => $this->due_date ?? null,
          'status'            => $this->status ?? null,
          'number'            => $this->number ?? null,
          'resource_id'       => $resource ? $resource->id : null,
          'resource'          => $resource ? $resource->name : null,
          'purchase_order_id' => $this->purchase_order_id ?? null,
          'purchase_order'    => $this->purchaseOrder ? $this->purchaseOrder->number : null,
          'project_id'        => $this->project_id,
          'total_price'       => UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2),
          'penalty'           => $this->purchaseOrder->penalty ?? null,
          'penalty_type'      => $this->purchaseOrder->penalty_type ?? null,
        ];
    }
}
