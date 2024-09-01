<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Contracts\Repositories\OrderRepositoryInterface;
use App\Models\Item;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\Order;
use App\Services\ItemService;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\OrderStatus;
use App\Models\Company;
use App\Models\Quote;
use App\Repositories\QuoteRepository;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;

/*
 *
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Order::class, function (Faker $faker, $attributes) {

    $legalEntity = LegalEntity::findOrFail($attributes['legal_entity_id']);
    $format = LegalEntitySetting::where('id', $legalEntity->legal_entity_setting_id)->first();
    $format->order_number += 1;
    $format->save();
    $date = $attributes['date'];
    $deadline = $faker->dateTimeInInterval($startDate = $date, $interval = '+ 30 days', $timezone = null);
    $deliveryDate = OrderStatus::isDelivered($attributes['status']) || OrderStatus::isInvoiced($attributes['status'])
        ? $faker->dateTimeBetween($startDate = $date, $endDate = $deadline, $timezone = null) : null;
  if ($deliveryDate > now()) {
      $deliveryDate = now();
  }
    $quote = Quote::find($attributes['quote_id']);

    return [
        'project_id' => $attributes['project_id'],
        'quote_id' => $attributes['quote_id'],
        'delivered_at' => $deliveryDate,
        'status' => $attributes['status'],
        'number' => transformFormat($format->order_number_format, $format->order_number),
        'date' => $date,
        'deadline' => $deadline,
        'reference' => $faker->text(50),
        'legal_entity_id' => $legalEntity->id,
        'vat_status' => $quote->vat_status,
        'vat_percentage' => $quote->vat_percentage,
        'master' => $quote->master,
    ];
});

$factory->afterCreating(Order::class, function ($order) {
    $items = Item::where('entity_id', $order->quote_id)->orderBy('order')->get();
  foreach ($items as $item) {
      Item::create([
          'entity_id' => $order->id,
          'entity_type' => Order::class,
          'service_id' => $item->service_id ?? null,
          'service_name' => $item->service_name ?? null,
          'description' => $item->description ?? null,
          'quantity' => $item->quantity ?? null,
          'unit' => $item->unit ?? null,
          'unit_price' => $item->unit_price ?? null,
          'company_id' => $item->company_id ?? null,
          'master_item_id' => $item->master_item_id ?? null,
          'order' => $item->order ?? 0,
      ]);
  }

  if ($order->master) {
      $company = Company::find(getTenantWithConnection());
      $quoteRepository =  App::make(QuoteRepository::class);
      $orderRepository =  App::make(OrderRepositoryInterface::class);
      $itemService = App::make(ItemService::class);
    if (!empty($order->quote->items)) {
        $quoteRepository->createShadowEntity($order->quote, $company, $order->load('items'), $itemService);
        Tenancy::setTenant($company);
    }
      $order->refresh();

      $shadowOrders = $order->shadows;
      $payload = [
          'status' =>$order->status
      ];
      foreach ($shadowOrders as $shadowOrder) {
          Tenancy::setTenant(Company::find($shadowOrder->shadow_company_id));
          $orderRepository->update($shadowOrder->shadow_id, $payload);
      }

      Tenancy::setTenant($company);
  }

    $itemService = App::make(ItemService::class);
    $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $order->id, Order::class);
});
