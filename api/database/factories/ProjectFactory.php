<?php

/** @var \Illuminate\Database\Eloquent\Factory $factory */

use App\Enums\CreditNoteType;
use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\InvoiceStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Employee;
use App\Models\Resource;
use App\Models\User;
use Faker\Generator as Faker;
use App\Repositories\PurchaseOrderRepository;
use App\Models\PurchaseOrder;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\InvoiceType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

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

$factory->define(App\Models\Project::class, function (Faker $faker, $attributes) {

    return [
        'name' => generateProjectUniqueName($attributes['contact_id']),
        'contact_id' => $attributes['contact_id'],
        'project_manager_id' => User::where('company_id', getTenantWithConnection())
            ->whereIn('role', [UserRole::pm()->getIndex(), UserRole::pm_restricted()->getIndex()])->get()->random(1)->first()->id,
        'sales_person_id' => $attributes['sales_person_id'],
        'created_at' => $attributes['created_at'],
        'budget' => $attributes['budget'],
        'price_modifiers_calculation_logic' => 0,
        'budget_usd' => 0
    ];
});

$factory->afterCreating(App\Models\Project::class, function ($project) {
    $date = $project->created_at;
    $customer = $project->contact->customer;

  if ($project->budget === 1) {
      $quote = factory(App\Models\Quote::class)
          ->create(['project_id' => $project->id, 'status' => QuoteStatus::invoiced()->getIndex(), 'date' => $date,
              'currency_code' => $customer->default_currency]);
      $orderStatus = OrderStatus::invoiced()->getIndex();
      $invoiceStatus = InvoiceStatus::paid()->getIndex();
  } else {
      $quote = factory(App\Models\Quote::class)
          ->create(['project_id' => $project->id, 'status' => array_rand(QuoteStatus::getIndices()), 'date' => $date,
              'currency_code' => $customer->default_currency]);

    if (QuoteStatus::isOrdered($quote->status)) {
        $orderStatusArray = [OrderStatus::draft()->getIndex(), OrderStatus::active()->getIndex(),
            OrderStatus::cancelled()->getIndex()];
        $key = array_rand($orderStatusArray);
        $orderStatus = $orderStatusArray[$key];
    }
    if (QuoteStatus::isInvoiced($quote->status)) {
        $orderStatus = OrderStatus::invoiced()->getIndex();
    }
      $statuses = InvoiceStatus::getIndices();
      $invoiceStatus = $statuses[array_rand($statuses)];
  }


  if ($quote->status == QuoteStatus::ordered()->getIndex() || $quote->status == QuoteStatus::invoiced()->getIndex()) {
      $order = factory(App\Models\Order::class)
          ->create(['project_id' => $project->id, 'date' => $date, 'quote_id' => $quote->id,
              'status' => $orderStatus, 'currency_code' => $quote->currency_code,
              'legal_entity_id' => $quote->legal_entity_id]);

    if (OrderStatus::isInvoiced($order->status) || OrderStatus::isDelivered($order->status)) {
      $invoice = factory(App\Models\Invoice::class)
          ->create(['project_id' => $project->id, 'date' => $order->delivered_at, 'type' => InvoiceType::accrec()->getIndex(),
              'currency_code' => $order->currency_code, 'status' => $invoiceStatus,
              'order_id' => $order->id, 'legal_entity_id' => $order->legal_entity_id]);

      if (mt_rand(0, 100) > 80) {
        factory(App\Models\CreditNote::class)
          ->create(['project_id' => $project->id, 'invoice_id' => $invoice->id,
              'currency_code' => $invoice->currency_code,
              'type' => CreditNoteType::accreccredit()->getIndex(), 'date' => $invoice->due_date]);
      }
    }

    if (OrderStatus::isActive($order->status) || OrderStatus::isDelivered($order->status) || OrderStatus::isInvoiced($order->status)) {
        $randomNumber = mt_rand(1, 5);
        $rate = getOrderEurToUsdRate(getTenantWithConnection(), $order->id);
      for ($x = 0; $x < $randomNumber; $x++) {
        $employee = Employee::where('status', EmployeeStatus::active()->getIndex())->get()->random(1)->first();
        $hours = mt_rand(1, 100);
        $employeeCost = safeDivide($employee->salary, $employee->working_hours) * $hours;
        $employeeRate = $employee->default_currency == CurrencyCode::USD()->getIndex() ? $rate : safeDivide(1, $rate);
        $month = Carbon::now()->firstOfMonth()->format('Y-m-d');
        $project->employees()->attach($employee->id, [
            'hours' => $hours,
            'month'=> $month,
            'employee_cost' => $employeeCost,
            'currency_rate_dollar' => $rate,
            'currency_rate_employee' => $employeeRate,
        ]);
        calculateEmployeeCosts(getTenantWithConnection(), $employee, $project, $hours, $rate, $employeeRate, $month);
      }

        $resource = Resource::get()->random(1)->first();
        $po = factory(App\Models\PurchaseOrder::class)
            ->create(['project_id' => $project->id, 'resource_id' => $resource->id, 'date' => $order->date,
                'legal_entity_id' => $order->legal_entity_id, 'currency_code' => $resource->default_currency]);

      if ($po->status == PurchaseOrderStatus::billed()->getIndex() || $po->status == PurchaseOrderStatus::paid()->getIndex() ) {
        $resourceInvoice = factory(App\Models\Invoice::class)->create([
        'project_id' => $project->id,
        'order_id' => $order->id,
        'purchase_order_id' => $po->id,
        'date' => $po->delivery_date,
        'type' => InvoiceType::accpay()->getIndex(),
        'pay_date' => $po->pay_date,
        'currency_code' => $po->currency_code,
        'legal_entity_id' => $po->legal_entity_id,
        'status' => PurchaseOrderStatus::isPaid($po->status) ? InvoiceStatus::paid()->getIndex() :
            array_rand([
                InvoiceStatus::draft()->getIndex(),
                InvoiceStatus::approval()->getIndex(),
                InvoiceStatus::authorised()->getIndex(),
                InvoiceStatus::submitted()->getIndex(),
                InvoiceStatus::paid()->getIndex(),
                InvoiceStatus::unpaid()->getIndex(),
                InvoiceStatus::cancelled()->getIndex(),
            ]),
        ]);
        $currentDate = Carbon::parse($project->created_at);
        $endDate = Carbon::now();
        if($currentDate->lessThanOrEqualTo($endDate)) {
            factory(App\Models\Invoice::class)->create([
                'project_id' => $project->id,
                'purchase_order_id' => $po->id,
                'date' => $currentDate->copy()->endOfMonth(),
                'type' => InvoiceType::accpay()->getIndex(),
                'pay_date' => PurchaseOrderStatus::isPaid($po->status) ? $po->pay_date : null,
                'currency_code' => $po->currency_code,
                'legal_entity_id' => $po->legal_entity_id,
                'status' => PurchaseOrderStatus::isPaid($po->status) ? InvoiceStatus::paid()->getIndex() :
                array_rand([
                    InvoiceStatus::draft()->getIndex(),
                    InvoiceStatus::approval()->getIndex(),
                    InvoiceStatus::authorised()->getIndex(),
                    InvoiceStatus::submitted()->getIndex(),
                    InvoiceStatus::paid()->getIndex(),
                    InvoiceStatus::unpaid()->getIndex(),
                    InvoiceStatus::cancelled()->getIndex(),
                ]),
            ]);
        }


        if (mt_rand(0, 100) > 80) {
          factory(App\Models\CreditNote::class)
          ->create(['project_id' => $project->id, 'invoice_id' => $resourceInvoice->id,
              'currency_code' => $resourceInvoice->currency_code,
              'type' => CreditNoteType::accpaycredit()->getIndex(), 'date' => $resourceInvoice->due_date]);
        }
      }

      if (PurchaseOrderStatus::isAuthorised($po->status) ||
            PurchaseOrderStatus::isBilled($po->status) ||
            PurchaseOrderStatus::isPaid($po->status)) {
        (new PurchaseOrderRepository(new PurchaseOrder))->updateRating($po, ['rating' => null]);
      }
    }
  }
});
