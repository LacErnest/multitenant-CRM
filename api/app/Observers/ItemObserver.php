<?php

namespace App\Observers;

use App\Contracts\Repositories\LegalEntityRepositoryInterface;
use App\Enums\UserRole;
use App\Jobs\XeroUpdate;
use App\Mail\ItemPriceChanged;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Models\Resource;
use App\Models\Service;
use App\Models\User;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use Exception;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Mail;

class ItemObserver
{
    public function created(Item $item)
    {
        if (auth()->user() && !(auth()->user() instanceof Resource)) {
            if (!(UserRole::isOwner(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role))) {
                if ($item->entity_type != PurchaseOrder::class) {
                    $service = Service::find($item->service_id);
                    if ($service && $service->price != $item->unit_price) {
                        $company_id = getTenantWithConnection();
                        $owners = User::where([['company_id', $company_id], ['role', UserRole::owner()->getIndex()]])->get();
                        $owners->each(function ($owner) use ($item, $company_id) {
                            Mail::to($owner->email)->queue(new ItemPriceChanged($company_id, auth()->user()->id, $item->service_id, $item->unit_price));
                        });
                    }
                }
            }
        }

        if ($item->entity && $item->entity->items && $item->order < $item->entity->items->count() - 1) {
            $items = $item->entity->items;
            $itemsBetween = $items->where('order', '>=', $item->order)
            ->where('id', '!=', $item->id)
            ->pluck('id')->toArray();
            Item::whereIn('id', $itemsBetween)->increment('order');
        }

        try {
            $companyId = getTenantWithConnection();
            $legalEntityRepository = App::make(LegalEntityRepositoryInterface::class);
            $legalEntity = $legalEntityRepository->firstByIdOrNull($item->entity->legal_entity_id);

            if (!$item->entity->shadow && $legalEntity && (new XeroAuthService($legalEntity))->exists()) {
                if ($item->entity_type == Quote::class) {
                    if ($item->entity->items->count() == 1) {
                        XeroUpdate::dispatch($companyId, 'created', Quote::class, $item->entity->id, $legalEntity->id)->onQueue('low');
                    }
                } elseif ($item->entity_type == PurchaseOrder::class) {
                    if ($item->entity->items->count() == 1) {
                        XeroUpdate::dispatch($companyId, 'created', PurchaseOrder::class, $item->entity->id, $legalEntity->id)->onQueue('low');
                    }
                } elseif ($item->entity_type == Invoice::class) {
                    if ($item->entity->items->count() == 1) {
                        XeroUpdate::dispatch($companyId, 'created', Invoice::class, $item->entity->id, $legalEntity->id)->onQueue('low');
                    }
                }
            }
        } catch (Exception $exception) {
        }
    }

    public function updated(Item $item)
    {
        $dirty = $item->getDirty();
        $original = $item->getOriginal();

        if (array_key_exists('order', $dirty) && $item->entity && $item->entity->items) {
            $items = $item->entity->items;
            if ($dirty['order'] > $original['order']) {
                $itemsBetween = $items->where('order', '>', $original['order'])
                ->where('order', '<=', $dirty['order'])
                ->where('id', '!=', $item->id)
                ->pluck('id')->toArray();
                Item::whereIn('id', $itemsBetween)->decrement('order');
            }

            if ($dirty['order'] < $original['order']) {
                $itemsBetween = $items->where('order', '<', $original['order'])
                ->where('order', '>=', $dirty['order'])
                ->where('id', '!=', $item->id)
                ->pluck('id')->toArray();
                Item::whereIn('id', $itemsBetween)->increment('order');
            }
        }
    }

    public function deleted(Item $item)
    {
        $items = $item->entity->items;
        if ($items) {
            $itemsHigher = $items->where('order', '>', $item->order)
            ->pluck('id')->toArray();
            Item::whereIn('id', $itemsHigher)->decrement('order');
        }
    }
}
