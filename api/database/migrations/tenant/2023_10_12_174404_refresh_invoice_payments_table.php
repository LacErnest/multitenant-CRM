<?php

use App\Models\Invoice;
use App\Services\InvoicePaymentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefreshInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (Invoice::all() as $invoice) {
            /**
             * @var InvoicePaymentService
             */
            $invoicePaymentService = app(InvoicePaymentService::class);
            $invoicePaymentService->refreshInvoicedPaymentsAmountForSingleInvoice($invoice);
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
