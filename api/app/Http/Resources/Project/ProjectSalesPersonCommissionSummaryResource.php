<?php

namespace App\Http\Resources\Project;

use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\ProjectEmployee;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectSalesPersonCommissionSummaryResource extends JsonResource
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
          'sales_person_id' => $this['sales_person_id'],
          'sales_person' => $this['sales_person'],
          'current_commission_model' => $this['current_commission_model'],
          'base_commission' => $this['base_commission'],
          'total_commission' => $this['total_commission'],
          'nb_commission' => $this['nb_commission'],
          'details' => [
              'columns' => [
                  [
                      'prop' => 'invoice_id',
                      'name' => 'Invoice',
                      'no_redirect' => false,
                      'type' => 'uuid',
                      'model' => 'invoice',
                  ],
                  [
                    'prop' => 'order_id',
                    'name' => 'Order',
                    'no_redirect' => false,
                    'type' => 'uuid',
                    'model' => 'order',
                  ],
                  [
                    'prop' => 'invoice_status',
                    'name' => 'Invoice Status',
                    'type' => 'enum',
                    'enum' => 'invoicestatus'
                  ],
                  [
                    'prop' => 'order_status',
                    'name' => 'Order Status',
                    'type' => 'enum',
                    'enum' => 'orderstatus'
                  ],
                  [
                    'prop' => 'gross_margin',
                    'name' => 'Gross Margin',
                    'type' => 'percentage',
                  ],
                  [
                    'prop' => 'commission_percentage',
                    'name' => 'Commission (%)',
                    'type' => 'percentage',
                  ],
                  [
                    'prop' => 'commission',
                    'name' => 'Commission',
                    'type' => 'decimal',
                  ],
                  [
                    'prop' => 'total_price',
                    'name' => 'Total Price',
                    'type' => 'decimal',
                  ],
                  [
                    'prop' => 'total_paid_amount',
                    'name' => 'Total Invoiced',
                    'type' => 'decimal',
                  ],
                  [
                    'prop' => 'paid_value',
                    'name' => 'Paid Value',
                    'type' => 'decimal',
                  ],
                  [
                    'prop' => 'paid_at',
                    'name' => 'Paid At',
                    'type' => 'date',
                  ],
                  [
                    'prop' => 'status',
                    'name' => 'Status',
                    'type' => 'string',
                  ],
              ],
              'rows' => ProjectSalesPersonCommissionResource::collection($this['commissions']),
          ],
        ];
    }
}
