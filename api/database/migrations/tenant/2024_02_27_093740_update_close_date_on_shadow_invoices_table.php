<?php

use App\Enums\InvoiceStatus;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class UpdateCloseDateOnShadowInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoices = Invoice::query()
            ->where('status', InvoiceStatus::paid()->getIndex())
            ->whereNotNull('close_date')
            ->where('master', true)
            ->get();

        $companyId = getTenantWithConnection();

        foreach ($invoices as $invoice) {
            foreach ($invoice->shadows as $shadow) {
                Tenancy::setTenant(Company::find($shadow->shadow_company_id));
                $shadowInvoice = Invoice::find($shadow->shadow_id);
                $shadowInvoice->update(['close_date' => $invoice->close_date]);
            }
        }
        
        Tenancy::setTenant(Company::find($companyId));
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
