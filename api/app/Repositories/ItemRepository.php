<?php


namespace App\Repositories;

use App\Models\Item;
use App\Models\PriceModifier;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;


class ItemRepository
{
    public function createItem(
        array $attributes,
        array $modifiers
    ): Item {
        DB::beginTransaction();
        try {
            $item = Item::create($attributes);

            if (!empty($modifiers)) {
                foreach ($modifiers as $modifier) {
                    PriceModifier::create(array_merge($modifier, [
                    'entity_id' => $item->id,
                    'entity_type' => Item::class,
                    ]));
                }
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $item;
    }

    public function updateItem(
        string $entityId,
        string $itemId,
        array $attributes,
        array $modifiers
    ): Item {
        $item = Item::findOrFail($itemId);

        if ($item->entity_id == $entityId) {
            DB::beginTransaction();
            try {
                $item->update($attributes);

                if (!empty($modifiers)) {
                    PriceModifier::where('entity_id', $itemId)
                    ->whereNotIn('id', array_filter(Arr::pluck($modifiers, 'id')))
                    ->delete();

                    foreach ($modifiers as $modifier) {
                        if (Arr::has($modifier, 'id')) {
                            $priceModifier = PriceModifier::where('id', $modifier['id'])->firstOrFail();
                            $priceModifier->update([
                                'type' => Arr::get($modifier, 'type'),
                                'description' => Arr::get($modifier, 'description'),
                                'quantity' => Arr::get($modifier, 'quantity'),
                                'quantity_type' => Arr::get($modifier, 'quantity_type'),
                            ]);
                        } else {
                            PriceModifier::create(array_merge($modifier, [
                            'entity_id' => $item->id,
                            'entity_type' => Item::class,
                            ]));
                        }
                    }
                } else {
                    PriceModifier::where('entity_id', $itemId)->delete();
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $item;
        }
        throw new ModelNotFoundException();
    }

    public function deleteItems(array $itemIds): void
    {
        DB::beginTransaction();
        try {
            $entityItems = Item::with('priceModifiers')->whereIn('id', $itemIds)->get();
            foreach ($entityItems as $item) {
                $item->priceModifiers()->delete();
                $item->delete();
            }
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function createPriceModifier(array $attributes): PriceModifier
    {
        DB::beginTransaction();
        try {
            $modifier = PriceModifier::create($attributes);
            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        return $modifier;
    }

    public function updatePriceModifier(
        string $entityId,
        string $priceModifierId,
        array $attributes
    ): PriceModifier {
        $modifier = PriceModifier::findOrFail($priceModifierId);

        if ($modifier->entity_id == $entityId) {
            DB::beginTransaction();
            try {
                $modifier->update($attributes);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }

            return $modifier;
        }
        throw new ModelNotFoundException();
    }

    public function deletePriceModifier($entityId, $priceModifierId): void
    {
        $modifier = PriceModifier::findOrFail($priceModifierId);

        if ($modifier->entity_id == $entityId) {
            DB::beginTransaction();
            try {
                $modifier->delete();
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                throw $e;
            }
        } else {
            throw new ModelNotFoundException();
        }
    }
}
