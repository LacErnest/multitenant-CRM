<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Contracts\Repositories\InvoiceRepositoryInterface;
use App\Enums\CommissionModel;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\Project;
use App\Models\SalesCommission;
use App\Models\User;
use App\Services\ItemService;
use Faker\Generator as Faker;
use Faker\Provider\Uuid;
use App\Enums\InvoiceStatus;
use App\Enums\VatStatus;
use App\Models\Company;
use App\Models\Order;
use App\Services\InvoicePaymentService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Tenancy\Facades\Tenancy;

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| This directory should contain each of the model factory definitions for
| your application. Factories provide a convenient way to generate new
| model instances for testing / seeding your application's database.
|
*/

$factory->define(Invoice::class, function (Faker $faker, $attributes) {

    $legalEntity = LegalEntity::findOrFail($attributes['legal_entity_id']);
    $format = LegalEntitySetting::where('id', $legalEntity->legal_entity_setting_id)->first();

  if (Arr::exists($attributes, 'purchase_order_id')) {
      $format->resource_invoice_number += 1;
      $number = transformFormat($format->resource_invoice_number_format, $format->resource_invoice_number);
  } else {
      $format->invoice_number += 1;
      $number = transformFormat($format->invoice_number_format, $format->invoice_number);
  }
    $format->save();

    $owner = User::where('company_id', getTenantWithConnection())->where('role', UserRole::owner()->getIndex())->first();
    $payDate = ($attributes['status'] === InvoiceStatus::paid()->getIndex()) ? $faker->dateTimeInInterval($startDate = $attributes['date'], $interval = '+ 10 days', $timezone = null) : null;
    $order = Order::find($attributes['order_id'] ?? null);
    return [
        'xero_id' => Uuid::uuid(),
        'project_id' => $attributes['project_id'],
        'order_id' => $attributes['order_id'] ?? null,
        'purchase_order_id' => $attributes['purchase_order_id'] ?? null,
        'created_by' => $owner->id,
        'pay_date' => $payDate,
        'status' => $attributes['status'],
        'type' => $attributes['type'],
        'date' => $attributes['date'],
        'due_date' => $faker->dateTimeInInterval($startDate = $attributes['date'], $interval = '+ 30 days', $timezone = null),
        'number' => $number,
        'reference' => $faker->text(50),
        'legal_entity_id' => $legalEntity->id,
        'updated_at' => $payDate ?? now(),
        'currency_code' => $attributes['currency_code'],
        'vat_status' => $order->vat_status ?? VatStatus::never()->getIndex(),
        'vat_percentage' => $order->vat_percentage ?? 0,
        'master' => $order->master ?? 0,
    ];
});

$factory->afterCreating(Invoice::class, function ($invoice) {

  if ($invoice->purchase_order_id) {
      $items = Item::where('entity_id', $invoice->purchase_order_id)->orderBy('order')->get();
  } else {
      $items = Item::where('entity_id', $invoice->order_id)->orderBy('order')->get();
  }

  foreach ($items as $item) {
      Item::create([
          'entity_id' => $invoice->id,
          'entity_type' => Invoice::class,
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

    $itemService = App::make(ItemService::class);
    $itemService->savePricesAndCurrencyRates(getTenantWithConnection(), $invoice->id, Invoice::class);
    $invoice->refresh();

  if ($invoice->master) {
      $invoiceRepository = App::make(InvoiceRepositoryInterface::class);
      $company = Company::find(getTenantWithConnection());
      $invoiceRepository->createShadowInvoices($invoice->order, $invoice->load('priceModifiers'), $company);
      $invoice->refresh();

      $attributes = ['status' => $invoice->status,'number'=>$invoice->number,'master'=>false];
      $oldDate = $invoice->date;
      $legalEntityId = $invoice->legal_entity_id;
      $shadowInvoices = $invoice->shadows;
    foreach ($shadowInvoices as $shadow) {
        Tenancy::setTenant(Company::find($shadow->shadow_company_id));
        $shadowInvoice = Invoice::find($shadow->shadow_id);
        $shadowInvoice->update($attributes);
      if ((Arr::exists($attributes, 'date') && !(strtotime($attributes['date']) == strtotime($oldDate))) ||
            (Arr::exists($attributes, 'legal_entity_id') && !($attributes['legal_entity_id'] == $legalEntityId))) {
        $itemService = App::make(ItemService::class);
        $itemService->savePricesAndCurrencyRates($shadow->shadow_company_id, $shadowInvoice->id, Invoice::class);
      }
    }
      Tenancy::setTenant($company);
  }

  if (InvoiceStatus::isPaid($invoice->status) && InvoiceType::isAccrec($invoice->type)) {
      $project = Project::find($invoice->project_id);
    if ($project) {
        $salesPerson = User::find($project->sales_person_id);
        if ($salesPerson->email) {
            $linkedUsers = User::where('email', $salesPerson->email)->orderBy('created_at', 'ASC')->get();
            $salesPerson = $linkedUsers->where('primary_account', true)->first() ?? $salesPerson;
        }
        $salesRecord = $salesPerson->salesCommissions->first();
      if ($salesRecord) {
        if (CommissionModel::isLead_generation($salesRecord->commission_model)) {
            $salesPerson->customerSales()->attach($project->contact->customer->id, [
                'project_id' => $project->id,
                'invoice_id' => $invoice->id,
                'pay_date' => $invoice->pay_date
            ]);
        } elseif (CommissionModel::isLead_generationB($salesRecord->commission_model)) {
            $salesPerson->customerSales()->attach($project->contact->customer->id, [
                'project_id' => $project->id,
                'invoice_id' => $invoice->id,
                'pay_date' => $invoice->pay_date
            ]);
        }
      }
    }
  }

  if (!empty($invoice->total_price) && InvoiceStatus::isPartial_paid($invoice->status)) {
      $totalAmount = get_total_price(Invoice::class, $invoice->id);
      $invoice->total_paid = round(rand($totalAmount / 3, $totalAmount));
      $invoice->save();
  }

  if (!empty($invoice->total_price) && (InvoiceStatus::isPaid($invoice->status) || InvoiceStatus::isPartial_paid($invoice->status))) {
      app(InvoicePaymentService::class)->migrateInvoicedPaymentsAmountForSingleInvoice($invoice);
  }

  if (empty($invoice->total_price) && InvoiceStatus::isPartial_paid($invoice->status)) {
      $invoice->status = InvoiceStatus::submitted()->getIndex();
      $invoice->save();
  }

  if (InvoiceStatus::isPaid($invoice->status) && empty($invoice->close_date)) {
      $invoice->close_date = $invoice->updated_at;
      $invoice->save();
  }

    $invoice->refresh();
});
