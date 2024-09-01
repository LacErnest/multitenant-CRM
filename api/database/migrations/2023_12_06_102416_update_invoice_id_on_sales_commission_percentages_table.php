<?php

use App\Enums\CommissionModel;
use App\Enums\CommissionPercentageType;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SalesCommissionPercentage;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class UpdateInvoiceIdOnSalesCommissionPercentagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::beginTransaction();
            foreach (Company::all() as $company) {
                Tenancy::setTenant($company);
                $orderIds = Order::pluck('id')->all();
                $percentages = SalesCommissionPercentage::whereIn('order_id', $orderIds)->get();
                foreach ($percentages as $percentage) {
                    $salesPerson = $percentage->salesPerson;
                    if (!$salesPerson->primary_account) {
                        $salesPerson = User::where([['email', $salesPerson->email], ['primary_account', true]])->first();
                    }
                    $salesCommissionRecordsCount = $salesPerson->salesCommissions->count();

                    if($percentage->order->shadow == 1) {
                        $percentage->delete();
                        continue;
                    }

                    $quote = $percentage->order->quote;
                    $invoiceIds = $percentage->order->invoices()->pluck('id')->toArray();
                    $quoteDate = $quote->date->format('Y-m-d');
                    $customerId = $quote->project->contact->customer->id;
                    if ($salesCommissionRecordsCount) {
                        $salesCommissionRecord = $salesPerson->salesCommissions()->whereDate('created_at', '<=', $quoteDate)
                            ->orderByDesc('created_at')->first();
                        if (!$salesCommissionRecord) {
                            $salesCommissionRecord = $salesPerson->salesCommissions->sortBy('created_at')->first();
                        }
                    }
                    if (!empty($salesCommissionRecord)) {
                        if (CommissionModel::isLead_generation($salesCommissionRecord->commission_model) && CommissionPercentageType::isCalculated($percentage->type)) {
                            $projectIds = $salesPerson->customerSales($customerId)
                                ->where('customer_id', $customerId)
                                ->orderBy('pay_date', 'ASC')->get()->map(function ($item) {
                                    return $item->pivot->project_id;
                                })->toArray();
                            $invoices = Invoice::whereIn('project_id', $projectIds)->orderBy('pay_date')->get();

                            if ($invoices->count() == 0) {
                                $invoices = $percentage->order->invoices;
                                if ($invoices->count() > 0) {
                                    $percentage->invoice_id = $invoices[0]->id;
                                    $percentage->save();
                                } else {
                                    $percentage->delete();
                                    continue;
                                }
                            }

                            if ($invoices->count() > 0 && in_array($invoices[0]->id, $invoiceIds)) {
                                $percentage->invoice_id = $invoices[0]->id;
                                $percentage->save();
                            }

                            if ($invoices->count() > 1 && in_array($invoices[1]->id, $invoiceIds)) {
                                $clone = $percentage->replicate();
                                $clone->invoice_id = $invoices[1]->id;
                                $clone->save();
                            }
                        } else {
                            $invoices = $percentage->order->invoices;
                            if ($invoices->count()) {
                                $percentage->invoice_id = $invoices[0]->id;
                                $percentage->save();
                                for ($i = 1; $i < $invoices->count(); $i++) {
                                    $clone = $percentage->replicate();
                                    $clone->invoice_id = $invoices[$i]->id;
                                    $clone->save();
                                }
                            } else {
                                $percentage->delete();
                            }
                        }
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollback();
            throw $th;
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
