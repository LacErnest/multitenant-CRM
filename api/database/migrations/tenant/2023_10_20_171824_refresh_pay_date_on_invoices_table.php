<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefreshPayDateOnInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach(Invoice::all() as $invoice){
            $last_payment = $invoice->payments()->orderByDesc('pay_date')->first();
            if(!empty($last_payment)){
                $invoice->pay_date = $last_payment->pay_date->format('Y-m-d');
                $invoice->update(
                    ['pay_date' => $last_payment->pay_date->format('Y-m-d')], 
                    ['timestamps' => false]
                );
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
