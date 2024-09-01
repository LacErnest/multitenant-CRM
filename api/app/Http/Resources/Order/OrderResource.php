<?php

namespace App\Http\Resources\Order;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Models\Company;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $company = Company::find(getTenantWithConnection());
        $currencyEur = false;
        if (UserRole::isAdmin(auth()->user()->role) ||
          $company->currency_code == CurrencyCode::EUR()->getIndex()) {
            $currencyEur = true;
        }

        $query = [
          'bool' => [
              'must' => [
                  ['match' => ['id' => $this->id]]
              ]
          ]
        ];

        $elasticOrder = Order::searchByQuery($query, null, [], []);

        $invoices = $this->invoices()->where([
          ['type', InvoiceType::accrec()->getIndex()],
          ['status', '!=', InvoiceStatus::cancelled()->getIndex()]
        ])->get();

        return [
          'markup'                => $currencyEur ? $elasticOrder[0]->markup : $elasticOrder[0]->markup_usd,
          'cost'                  => $currencyEur ? $elasticOrder[0]->costs : $elasticOrder[0]->costs_usd,
          'id'                    => $this->id,
          'project_id'            => $this->project_id ?? null,
          'project_manager'       => $this->project && $this->project->projectManager ? $this->project->projectManager->name : null,
          'project_manager_id'    => $this->project && $this->project->project_manager_id ? $this->project->project_manager_id : null,
          'quote_id'              => $this->quote_id ?? null,
          'date'                  => $this->date ?? null,
          'deadline'              => $this->deadline ?? null,
          'delivered_at'          => $this->delivered_at ?? null,
          'status'                => $this->status ?? null,
          'number'                => $this->number ?? null,
          'reference'             => $this->reference ?? null,
          'currency_code'         => $this->currency_code ?? null,
          'created_at'            => $this->created_at,
          'updated_at'            => $this->updated_at ?? null,
          'items'                 => ItemResource::collection($this->items->load('entity')->sortBy('order')),
          'price_modifiers'       => ModifierResource::collection($this->priceModifiers->load('entity')->sortBy('quantity_type')),
          'total_price'           => $this->manual_input ? $this->manual_price : ($currencyEur ? $this->total_price : $this->total_price_usd),
          'total_vat'             => $this->manual_input ? $this->manual_vat : ($currencyEur ? $this->total_vat : $this->total_vat_usd),
          'manual_input'          => $this->manual_input,
          'tax_rate'              => empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage,
          'legal_entity_id'       => $this->legal_entity_id,
          'legal_entity'          => $this->legalEntity ? $this->legalEntity->name : null,
          'legal_country'         => $this->legalEntity ? $this->legalEntity->address->country : null,
          'total_invoices_price'  => $currencyEur ? $invoices->sum('total_price') : $invoices->sum('total_price_usd'),
          'has_media'             => $this->getMedia('document_order')->first()->file_name ?? null,
          'potential_markup'      => $currencyEur ? $elasticOrder[0]->potential_markup : $elasticOrder[0]->potential_markup_usd,
          'potential_gm'          => $currencyEur ? $elasticOrder[0]->potential_gm : $elasticOrder[0]->potential_gm_usd,
          'gross_margin'          => $currencyEur ? $elasticOrder[0]->gross_margin : $elasticOrder[0]->gross_margin_usd,
          'potential_cost'        => $currencyEur ? $elasticOrder[0]->potential_costs : $elasticOrder[0]->potential_costs_usd,
          'master'                => $this->master,
          'shadow'                => $this->shadow,
          'total_shadows'         => $this->shadows->count(),
          'vat_status'            => $this->vat_status,
          'price_user_currency'   => $currencyEur ? $this->total_price : $this->total_price_usd
        ];
    }
}
