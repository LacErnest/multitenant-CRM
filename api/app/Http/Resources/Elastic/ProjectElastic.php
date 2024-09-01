<?php

namespace App\Http\Resources\Elastic;

use App\Enums\CurrencyCode;
use App\Enums\PurchaseOrderStatus;
use App\Enums\QuoteStatus;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\DB;

class ProjectElastic extends JsonResource
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

        $po = $this->purchaseOrders()->whereIn('status', [PurchaseOrderStatus::submitted()->getIndex(), PurchaseOrderStatus::authorised()->getIndex(), PurchaseOrderStatus::billed()->getIndex(), PurchaseOrderStatus::paid()->getIndex()])->get();
        $costs = $po->sum('total_price');
        $vat = $po->sum('total_vat');
        $costs_usd = $po->sum('total_price_usd');
        $vat_usd = $po->sum('total_vat_usd');

        $salesPersons = $this->salesPersons;

        return
          [
              'id' => $this->id,
              'name' => $this->name ?? null,
              'contact' => $this->contact ? $this->contact->name : null,
              'contact_id' => $this->contact ? $this->contact->id : null,
              'customer' => $this->contact ? $this->contact->customer->name : null,
              'customer_id' => $this->contact ? $this->contact->customer->id : null,
              'project_manager' => $this->projectManager ? $this->projectManager->name : null,
              'project_manager_id' => $this->projectManager ? $this->projectManager->id : null,
              'sales_person' => !$salesPersons->count() == 0 ? getNames($salesPersons) : null,
              'sales_person_id' => !$salesPersons->count() == 0 ? $salesPersons->pluck('id') : null,
              'budget' => $this->budget ? ceiling((float)$this->budget, 2) : null,
              'budget_usd' => $this->budget_usd ? ceiling((float)$this->budget_usd, 2) : null,
              'po_costs' => ceiling($costs, 2),
              'po_vat' => ceiling($vat, 2),
              'employee_costs' => ceiling($this->employee_costs, 2),
              'po_costs_usd' => ceiling($costs_usd, 2),
              'po_vat_usd' => ceiling($vat_usd, 2),
              'employee_costs_usd' => ceiling($this->employee_costs_usd, 2),
              'price_modifiers_calculation_logic'=> $this->price_modifiers_calculation_logic ?? 1,
              'created_at' => $this->created_at->timestamp,
              'updated_at' => $this->updated_at->timestamp ?? null
          ];
    }
}
