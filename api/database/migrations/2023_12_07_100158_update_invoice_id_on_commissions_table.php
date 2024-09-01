<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Commission;
use App\Models\Company;
use App\Models\Order;
use App\Models\Quote;
use App\Repositories\Cache\CacheCommissionRepository;
use App\Services\AnalyticService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class UpdateInvoiceIdOnCommissionsTable extends Migration
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
            $cacheCommissionRepository = App::make(CacheCommissionRepository::class);

            foreach (Company::all() as $company) {
                Tenancy::setTenant($company);
                $orders = Order::whereHas('invoices', function ($query) {
                    $query->where('status', InvoiceStatus::paid()->getIndex())
                        ->where('type', InvoiceType::accrec()->getIndex());
                })->get();
                foreach ($orders as $order) {
                    if($order->shadow == 1) {
                        continue;
                    }
                    $query = [
                        'bool' => [
                            'must' => [
                                ['match' => ['id' => $order->quote->id]]
                            ]
                        ]
                    ];

                    $elasticQuote = Quote::searchByQuery($query, null, [], []);
                    $quote = ['_source' => $elasticQuote->toArray()[0]];
                    $quote['_source']['date'] = strtotime($quote['_source']['date']);

                    $invoices = $order->invoices()
                        ->where('status', InvoiceStatus::paid()->getIndex())
                        ->where('type', InvoiceType::accrec()->getIndex())
                        ->orderBy('pay_date', 'DESC')->get();
                    $commissions = [];
                    foreach ($invoices as $invoice) {
                        $quote['_source']['invoiced_total_price'] = $invoice->total_price;
                        $quote['_source']['invoice_id'] = $invoice->id;
                        $base_commissions = $cacheCommissionRepository->formatCommissionQuoteData($quote, [], $company, false);
                        if (!empty($base_commissions)) {
                            foreach ($base_commissions['commissions'] as $base_commission) {
                                if(!isset($commissions[$base_commission['sales_person_id']])){
                                    $commissions[$base_commission['sales_person_id']] = [];
                                }
                                if (isset($commissions[$base_commission['sales_person_id']][$invoice->id])) {
                                    $commissions[$base_commission['sales_person_id']][$invoice->id] += $base_commission['commission'];
                                } else {
                                    $commissions[$base_commission['sales_person_id']][$invoice->id] = $base_commission['commission'];
                                }
                            }
                        }
                    }
                    foreach ($commissions as $salesPersionId => $sales_commissions) {
                        $commission = Commission::where('order_id', $order->id)
                            ->where('sales_person_id', $salesPersionId)
                            ->first();
                        if (empty($commission)) {
                            continue;
                        }
                        $paidAmount = $commission->paid_value;
                        foreach ($sales_commissions as $invoiceId => $total_commission) {
                            if ($paidAmount <= $total_commission) {
                                if (empty($commission->invoice_id)) {
                                    $commission->invoice_id = $invoiceId;
                                    $commission->paid_value = $paidAmount;
                                    $commission->total = $total_commission;
                                    $commission->save();
                                } else {
                                    $clone = $commission->replicate();
                                    $clone->invoice_id = $invoiceId;
                                    $clone->paid_value = $paidAmount;
                                    $clone->total = $total_commission;
                                    $clone->save();
                                }
                                break;
                            } else if (empty($commission->invoice_id)) {
                                $commission->invoice_id = $invoiceId;
                                $commission->paid_value = $total_commission;
                                $commission->total = $total_commission;
                                $commission->save();
                                $paidAmount -= $total_commission;
                            } else {
                                $clone = $commission->replicate();
                                $clone->invoice_id = $invoiceId;
                                $clone->paid_value = $total_commission;
                                $clone->total = $total_commission;
                                $clone->save();
                                $paidAmount -= $total_commission;
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
