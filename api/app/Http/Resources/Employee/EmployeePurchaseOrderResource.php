<?php

namespace App\Http\Resources\Employee;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Http\Resources\Resource\PurchaseOrderInvoiceResource;
use App\Models\Company;
use App\Services\EmployeeService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class EmployeePurchaseOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $companyCurrency = Company::find(getTenantWithConnection())->currency_code;
        $resource = null;
        if ($this->resource_id) {
            $employeeService = App::make(EmployeeService::class);
            $resource = $employeeService->findBorrowedEmployee($this->resource_id);
        }

        return [
          'id' => $this->id,
          'xero_id' => $this->xero_id,
          'project_id' => $this->project_id,
          'project' => $this->project ? $this->project->name : null,
          'is_contractor' => true,
          'resource_id' => $this->resource_id,
          'resource' => $resource ? $resource->name : null,
          'date' => $this->date,
          'delivery_date' => $this->delivery_date,
          'pay_date' => $this->pay_date,
          'status' => $this->status,
          'number' => $this->number,
          'reference' => $this->reference,
          'currency_code' => $this->currency_code,
          'total_price' => UserRole::isAdmin(auth()->user()->role) || $companyCurrency == CurrencyCode::EUR()->getIndex() ?
                  ceiling($this->total_price, 2) : ceiling($this->total_price_usd, 2),
          'total_vat' => UserRole::isAdmin(auth()->user()->role) || $companyCurrency == CurrencyCode::EUR()->getIndex() ?
                  ceiling($this->total_vat, 2) : ceiling($this->total_vat_usd, 2),
          'details' => [
              'columns' => [
                  [
                      'prop' => 'number',
                      'name' => 'number',
                      'type' => 'string',
                  ],
                  [
                      'prop' => 'date',
                      'name' => 'date',
                      'type' => 'date',
                  ],
                  [
                      'prop' => 'due_date',
                      'name' => 'due date',
                      'type' => 'date',
                  ],
                  [
                      'prop' => 'status',
                      'name' => 'status',
                      'type' => 'enum',
                      'enum' => 'invoicestatus',
                  ],
                  [
                      'prop' => 'pay_date',
                      'name' => 'pay date',
                      'type' => 'date',
                  ],
                  [
                      'prop' => 'reference',
                      'name' => 'reference',
                      'type' => 'string',
                  ],
                  [
                      'prop' => 'total_price',
                      'name' => 'total price',
                      'type' => 'decimal',
                  ],
              ],
              'rows' => PurchaseOrderInvoiceResource::collection($this->invoices),
          ]
        ];
    }
}
