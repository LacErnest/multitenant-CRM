<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CurrencyCode;
use App\Enums\PurchaseOrderStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class PurchaseOrderElastic extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $this->syncOriginal();
        $legacyCustomer = false;
        $intraCompany = false;
        $intraCompanyId = null;

        if ($this->project) {
            $po = $this->project->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()])->get();
            $costs = $po->sum('total_price');
            $vat = $po->sum('total_vat');
            $costs_usd = $po->sum('total_price_usd');
            $vat_usd = $po->sum('total_vat_usd');
        }

        $resource = null;
        $isContractor = false;
        $customerTotalPrice = 0;
        if ($this->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->resource_id);
                $isContractor = true;
            }
        }

        if ($this->currency_rate_customer) {
            $customerTotalPrice = $this->total_price * $this->currency_rate_customer;
        }

        $customerId = $this->project && $this->project->contact ? $this->project->contact->customer->id : null;

        if ($customerId) {
            $customer = Customer::find($customerId);

            if ($customer && $customer->legacyCompanies()->where('company_id', getTenantWithConnection())->exists()) {
                $legacyCustomer = true;
            }

            if ($customer->intra_company) {
                $intraCompany = true;
                $company = Company::where('name', $customer->name)->first();

                if ($company) {
                    $intraCompanyId = $company->id;
                }
            }
        }

        return [
          'id' => $this->id,
          'xero_id' => $this->xero_id ?? null,
          'project_info' => [
              'id' => $this->project ? $this->project->id : null,
              'po_cost' => $this->project ? ceiling($costs, 2) : 0,
              'po_vat' => $this->project ? ceiling($vat, 2) : 0,
              'employee_cost' => $this->project ? ceiling($this->project->employee_costs, 2) : 0,
              'po_cost_usd' => $this->project ? ceiling($costs_usd, 2) : 0,
              'po_vat_usd' => $this->project ? ceiling($vat_usd, 2) : 0,
              'employee_cost_usd' => $this->project ? ceiling($this->project->employee_costs_usd, 2) : 0,
          ],
          'project' => $this->project ? $this->project->name : null,
          'project_id' => $this->project ? $this->project->id : null,
          'contact' => $this->project && $this->project->contact ? $this->project->contact->name : null,
          'contact_id' => $this->project && $this->project->contact ? $this->project->contact->id : null,
          'customer' => $this->project && $this->project->contact ? $this->project->contact->customer->name : null,
          'customer_id' => $this->project && $this->project->contact ? $this->project->contact->customer->id : null,
          'resource' => $resource ? $resource->name : null,
          'resource_id' => $this->resource_id,
          'order' => $this->project->order ? $this->project->order->number : null,
          'order_id' => $this->project->order ? $this->project->order->id : null,
          'date' => $this->date->timestamp ?? null,
          'delivery_date' => $this->delivery_date->timestamp ?? null,
          'pay_date' => $this->pay_date->timestamp ?? null,
          'authorised_date' => $this->authorised_date->timestamp ?? null,
          'status' => $this->status ?? null,
          'number' => $this->number ?? null,
          'reference' => $this->reference ?? null,
          'reason_of_rejection' => $this->reason_of_rejection ?? null,
          'reason_of_penalty' => $this->reason_of_penalty ?? null,
          'currency_code' => $this->currency_code ?? null,
          'customer_currency_code' => $this->project && $this->project->contact && $this->project->contact->customer ? $this->project->contact->customer->default_currency : null,
          'total_price' => ceiling($this->total_price, 2),
          'total_vat' => ceiling($this->total_vat, 2),
          'total_price_usd' => ceiling($this->total_price_usd, 2),
          'total_vat_usd' => ceiling($this->total_vat_usd, 2),
          'customer_total_price' => ceiling(get_total_price(PurchaseOrder::class, $this->id), 2),
          'penalty' => $this->penalty ?? null,
          'penalty_type' => $this->penalty_type ?? null,
          'created_at' => $this->created_at->timestamp,
          'updated_at' => $this->updated_at->timestamp ?? null,
          'legal_entity_id' => $this->legal_entity_id,
          'legal_entity' => $this->legalEntity ? $this->legalEntity->name : null,
          'resource_country' => $resource ? $resource->country : null,
          'resource_currency' => $resource ? $resource->default_currency : null,
          'company_id' => getTenantWithConnection(),
          'shadow_price' => 0,
          'shadow_price_usd' => 0,
          'shadow_vat' => 0,
          'shadow_vat_usd' => 0,
          'manual_price' => $this->manual_price ?? null,
          'manual_vat' => $this->manual_vat ?? null,
          'is_contractor' => $isContractor,
          'purchase_order_project' => $this->project && $this->project->purchase_order_project,
          'intra_company' => $intraCompany,
          'intra_company_id' => $intraCompanyId,
          'legacy_customer' => $legacyCustomer,
        ];
    }
}
