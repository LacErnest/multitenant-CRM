<?php

use App\DTO\InvoicePayments\CreateInvoicePaymentDTO;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\InvoicePaymentService;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RecalculateInvoicePaymentAmount extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**
         * @var InvoicePaymentService
         */
        $invoicePaymentService = app(InvoicePaymentService::class);

        foreach (Invoice::all() as $invoice) {
            if (InvoiceStatus::isPaid($invoice->status)) {
                $invoiceTotalPrice = get_total_price(Invoice::class, $invoice->id);
                $totalPaidAmount = $invoice->payments->sum('pay_amount');
                $remainingAmount = $invoiceTotalPrice - $totalPaidAmount;
                $last_payment = $invoice->payments()->orderByDesc('pay_date')->first();
                if (!empty($last_payment)) {
                    $last_payment->pay_amount += $remainingAmount;
                    $last_payment->save();
                } else if ($remainingAmount > 0) {
                    $payDate = $invoice->pay_date->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');
                    $invoicePaymentService->createWithoutCheckInvoiceStatus(
                        $invoice,
                        new CreateInvoicePaymentDTO([
                            'pay_amount' => $remainingAmount,
                            'pay_date' => $payDate,
                            'currency_code'=>$invoice->currency_code,
                            'pay_full_price'=>true
                        ])
                    );
                }
            }
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
