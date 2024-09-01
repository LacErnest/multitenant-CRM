<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class SetValueToSubmittedInvoicesDates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoices = Invoice::whereIn('status', [
            InvoiceStatus::submitted()->getIndex(),
            InvoiceStatus::paid()->getIndex(),
            InvoiceStatus::partial_paid()->getIndex(),
        ])->get();

        foreach ($invoices as $invoice) {
            $invoice->submitted_date = $invoice->updated_at->format('Y-m-d H:i:s');
            $invoice->date;
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
