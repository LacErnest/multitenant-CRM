<?php


namespace App\Repositories;

use App\Enums\EntityPenaltyType;
use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Models\Setting;
use App\Services\ItemService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * Class PurchaseOrderRepository
 *
 * @deprecated
 */
class PurchaseOrderRepository
{
    protected PurchaseOrder $purchaseOrder;

    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function create($project_id, $attributes)
    {
        $attributes['project_id'] = $project_id;
        $attributes['created_by'] = auth()->user()->id;
        $format = Setting::first();
        $attributes['number'] = transformFormat($format->purchase_order_number_format, $format->purchase_order_number + 1);
        $purchaseOrder = $this->purchaseOrder->create($attributes);
        $format->purchase_order_number += 1;
        $format->save();
        return $purchaseOrder;
    }

    public function update(PurchaseOrder $purchaseOrder, $attributes)
    {
        $oldDate = $purchaseOrder->date;
        $legalEntityId = $purchaseOrder->legal_entity_id;

        if (array_key_exists('penalty', $attributes) && !($attributes['penalty'] === null)) {
            if (isset($attributes['penalty_type']) && $attributes['penalty_type'] == EntityPenaltyType::fixed()->getIndex()) {
                $penalty = $attributes['penalty'];
                $attributes['manual_price'] = $purchaseOrder->manual_price  - $penalty;
                $attributes['manual_vat'] = $purchaseOrder->manual_vat - $penalty;
                $attributes['total_price'] = $purchaseOrder->total_price - $penalty;
                $attributes['total_price_usd'] = $purchaseOrder->total_price_usd - $penalty;
                $attributes['total_vat'] = $purchaseOrder->total_vat - $penalty;
                $attributes['total_vat_usd'] = $purchaseOrder->total_vat_usd - $penalty;
            } else {
                $penalty = (100 - $attributes['penalty']) / 100;
                $attributes['manual_price'] = $purchaseOrder->manual_price * $penalty;
                $attributes['manual_vat'] = $purchaseOrder->manual_vat * $penalty;
                $attributes['total_price'] = $purchaseOrder->total_price * $penalty;
                $attributes['total_price_usd'] = $purchaseOrder->total_price_usd * $penalty;
                $attributes['total_vat'] = $purchaseOrder->total_vat * $penalty;
                $attributes['total_vat_usd'] = $purchaseOrder->total_vat_usd * $penalty;
            }
        }

        if (array_key_exists('status', $attributes)) {
            $nextStatus = $attributes['status'];
            if (PurchaseOrderStatus::isAuthorised($nextStatus)) {
                $attributes['authorised_date'] = now();
                $attributes['authorised_by'] = auth()->user()->id;
            }
            if (PurchaseOrderStatus::isPaid($nextStatus)) {
                $attributes['processed_by'] = auth()->user()->id;
            }
            if (PurchaseOrderStatus::isDraft($nextStatus)) {
                $attributes['authorised_by'] = null;
                $attributes['processed_by'] = null;
                $attributes['pay_date'] = null;
                $attributes['penalty'] = null;
                $attributes['penalty_type'] = null;
                $attributes['reason_of_penalty'] = null;
                $attributes['reason_of_rejection'] = null;
            }
        }

        if (Arr::exists($attributes, 'legal_entity_id') && !($attributes['legal_entity_id'] === null)) {
            if (Str::startsWith($purchaseOrder->number, 'DRAFT-')) {
                $format = getSettingsFormat($attributes['legal_entity_id']);
                $attributes['number'] = transformFormat($format->purchase_order_number_format, $format->purchase_order_number + 1);
                $format->purchase_order_number += 1;

                $format->save();
            }
        }

        $purchaseOrder = tap($purchaseOrder)->update($attributes);
        $this->updateRating($purchaseOrder, $attributes);

        if (Arr::exists($attributes, 'date') && !(strtotime($attributes['date']) == strtotime($oldDate))  ||
          (Arr::exists($attributes, 'legal_entity_id') && !($attributes['legal_entity_id'] == $legalEntityId))) {
            $itemService = App::make(ItemService::class);
            $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $purchaseOrder->id, PurchaseOrder::class);
        }
        return $purchaseOrder->refresh();
    }

    public function updateRating(PurchaseOrder $purchaseOrder, $attributes)
    {
        if (array_key_exists('rating', $attributes)) {
            $resource       = $purchaseOrder->resource()->get()->first();
            if ($resource) {
                $purchaseOrders = $resource->purchaseOrders()->get();
                $ratings        = 0;
                $count          = 0;

                foreach ($purchaseOrders as $purchaseOrder) {
                    if ($purchaseOrder->rating) {
                        $ratings += $purchaseOrder->rating;
                        $count++;
                    }
                }

                $average_rating = $ratings/$count;
                $average_rating = number_format($average_rating, 2, '.', '');
                $resource->average_rating = $average_rating;
                $resource->save();
            }
        }
    }
}
