<?php

namespace App\Http\Resources\Invoice;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Resources\CreditNote\CreditNoteResource;
use App\Http\Resources\Item\ItemResource;
use App\Http\Resources\Item\ModifierResource;
use App\Models\Company;
use App\Models\CompanyNotificationSetting;
use App\Models\EmailTemplate;
use App\Models\Invoice;
use App\Models\Resource;
use App\Services\EmailTemplateService;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class InvoiceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $resource = null;
        $company = Company::find(getTenantWithConnection());
        $companyNotificationSettings = CompanyNotificationSetting::find(getTenantWithConnection());
        $currencyRateEurToUSD = 0;
        if ($this->purchaseOrder && $this->purchaseOrder->resource_id) {
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->purchaseOrder->resource_id);
            if (!$resource) {
                $employeeService = App::make(EmployeeService::class);
                $resource = $employeeService->findBorrowedEmployee($this->purchaseOrder->resource_id);
            }
        }
        $totalPaidAmount = ceiling($this->payments ? $this->payments->sum('pay_amount') : 0, 2);
        if ($company->currency_code == CurrencyCode::USD()->getIndex() && !empty($this->currency_rate_company)) {
            $currencyRateEurToUSD = safeDivide(1, $this->currency_rate_company);
        } else {
            $currencyRateEurToUSD = $this->currency_rate_company;
        }

        if (!$this->manual_input && $this->currency_code != $company->currency_code) {
            $customerTotalPrice =  ceiling(entityPrice(Invoice::class, $this->id, false, $this->currency_rate_customer, true), 2);
        } else {
            $customerTotalPrice = entityPrice(Invoice::class, $this->id, false, 1, true);
        }

        if (empty($this->project->price_modifiers_calculation_logic)) {
            $modifiers = $this->priceModifiers->load('entity')->sortBy('quantity_type');
        } else {
            $modifiers = $this->priceModifiers->load('entity');
        }

        if (!empty($this->email_template_id)) {
            $emailTemplateId = $this->email_template_id;
        } else {
            $emailTemplateService = app(EmailTemplateService::class);
            $defaultEmailTemplate = $emailTemplateService->getDefaultEmailTemplate();
            $emailTemplateId = optional($defaultEmailTemplate)->id;
        }


        return [
          'id'                     => $this->id,
          'created_by'             => $this->created_by,
          'xero_id'                => $this->xero_id ?? null,
          'project_id'             => $this->project_id ?? null,
          'project'                => $this->project ? $this->project->name : null,
          'order_id'               => $this->order_id,
          'order'                  => $this->order ? $this->order->number : null,
          'order_legal_entity'     => !empty($this->order->legalEntity) ? $this->order->legalEntity->name : null,
          'resource_id'            => $resource ? $resource->id : null,
          'resource'               => $resource ? $resource->name : null,
          'resource_country'       => $resource ? $resource->country : null,
          'resource_not_vat_liable'=> $resource ? $resource->non_vat_liable : false,
          'type'                   => $this->type ?? null,
          'date'                   => $this->date ?? null,
          'due_date'               => $this->due_date ?? null,
          'close_date'             => $this->close_date ?? null,
          'status'                 => $this->status ?? null,
          'pay_date'               => $this->pay_date ?? null,
          'number'                 => $this->number ?? null,
          'reference'              => $this->reference ?? null,
          'currency_code'          => $this->currency_code ?? null,
          'currency_rate_customer' => $this->currency_rate_customer ?? null,
          'created_at'             => $this->created_at,
          'updated_at'             => $this->updated_at,
          'payment_terms'          => $this->payment_terms,
          'items'                  => ItemResource::collection($this->items->load('entity')->sortBy('order')),
          'price_modifiers'        => ModifierResource::collection($modifiers),
          'total_price'            => $this->manual_input ? $this->manual_price : (UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              $this->total_price : $this->total_price_usd),
          'total_vat'              => $this->manual_input ? $this->manual_vat : (UserRole::isAdmin(auth()->user()->role) ||
              Company::find(getTenantWithConnection())->currency_code == CurrencyCode::EUR()->getIndex() ?
              $this->total_vat : $this->total_vat_usd),
          'manual_input'           => $this->manual_input,
          'down_payment'           => $this->down_payment ?? 0,
          'tax_rate'               => empty($this->vat_percentage) ? getTaxRate($this->date, $this->legal_entity_id) : $this->vat_percentage,
          'legal_entity_id'        => $this->legal_entity_id,
          'download'               => $this->hasMedia('invoice_uploads'),
          'purchase_order_id'      => $this->purchase_order_id,
          'legal_entity'           => $this->legalEntity ? $this->legalEntity->name : null,
          'legal_country'          => $this->legalEntity ? $this->legalEntity->address->country : null,
          'master'                 => $this->master,
          'vat_status'             => $this->vat_status,
          'down_payment_status'    => $this->down_payment_status ?? 2,
          'penalty'                => $this->purchaseOrder->penalty ?? null,
          'penalty_type'           => $this->purchaseOrder->penalty_type ?? null,
          'reason_of_penalty'      => $this->purchaseOrder->reason_of_penalty ?? null,
          'total_paid_amount'      => $totalPaidAmount,
          'total_paid_amount_usd'  => $totalPaidAmount * $currencyRateEurToUSD,
          'customer_total_price'   => get_total_price(Invoice::class, $this->id),
          'eligible_for_earnout'   => $this->eligible_for_earnout,
          'customer_notified_at'   => $this->customer_notified_at,
          'email_template_id'      => $emailTemplateId,
          'send_client_reminders'  => $this->send_client_reminders,
          'email_template_globally_disabled' => $companyNotificationSettings->globally_disabled_email ?? false,
          'details'                => [
              'columns' => [
                  [
                      'prop' => 'date',
                      'name' => 'date',
                      'type' => 'date'
                  ],
                  [
                      'prop' => 'status',
                      'name' => 'status',
                      'type' => 'enum',
                      'enum' => 'creditnotestatus'
                  ],
                  [
                      'prop' => 'type',
                      'name' => 'type',
                      'type' => 'enum',
                      'enum' => 'creditnotetype'
                  ],
                  [
                      'prop' => 'number',
                      'name' => 'number',
                      'type' => 'string'
                  ],
                  [
                      'prop' => 'total_price',
                      'name' => 'total price',
                      'type' => 'decimal'
                  ],
                  [
                      'prop' => 'total_vat',
                      'name' => 'total vat',
                      'type' => 'decimal'
                  ]
              ],
              'rows' => CreditNoteResource::collection($this->creditNotes),
          ],
        ];
    }
}
