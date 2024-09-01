<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class RevertEarnoutsMigration extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoices = Invoice::query()
            ->with('shadows')
            ->with('masterInvoice')
            ->where('status', InvoiceStatus::paid()->getIndex())
            ->where('type', InvoiceType::accrec()->getIndex())
            ->get();
            
        foreach ($invoices as $invoice) {

            if (!$invoice->masterInvoice &&  $invoice->shadow) {
                $invoice->update(['shadow' => false]);
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
