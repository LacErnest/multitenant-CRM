<?php

use App\Enums\InvoiceType;
use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateInvoicesWithoutOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoices = Invoice::where([['type', InvoiceType::accrec()->getIndex()], ['order_id', null]])->get();

        foreach ($invoices as $invoice) {
            $invoice->order_id = $invoice->project->order->id;
            $invoice->save();
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
