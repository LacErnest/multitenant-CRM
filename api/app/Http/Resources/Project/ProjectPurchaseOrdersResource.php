<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class ProjectPurchaseOrdersResource extends JsonResource
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
        if ($this->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->resource_id);
            }
        }

        return [
          'id' => $this->id,
          'resource' => $resource ? $resource->name : null,
          'resource_id' => $this->resource_id,
          'date' => $this->date ?? null,
          'delivery_date' => $this->delivery_date ?? null,
          'status' => $this->status ?? null,
          'number' => $this->number ?? null,
          'total_price' => UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2),
        ];
    }
}
