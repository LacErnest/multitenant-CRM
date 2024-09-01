<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ResetCurencyCodeOnInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach(InvoicePayment::all() as $payment){
            if(!empty($payment->invoice)){
                $payment->currency_code = $payment->invoice->currency_code;
                if(InvoiceStatus::isPaid($payment->invoice->status)){
                    $payment->pay_amount = $payment->invoice->manual_price;
                }
                $payment->save();
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
