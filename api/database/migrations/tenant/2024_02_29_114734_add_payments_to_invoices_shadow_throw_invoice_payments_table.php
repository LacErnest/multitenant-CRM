<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\InvoicePaymentService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class AddPaymentsToInvoicesShadowThrowInvoicePaymentsTable extends Migration
{
    /**
     * @var InvoicePaymentService
     */
    private $invoicePaymentService;

    public function __construct()
    {
        $this->invoicePaymentService = app(InvoicePaymentService::class);
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $invoices = Invoice::master()
            ->with('shadows')
            ->with('masterInvoice')
            ->where('status', InvoiceStatus::paid()->getIndex())
            ->where('type', InvoiceType::accrec()->getIndex())
            ->get();

        $companyId = getTenantWithConnection();

        foreach ($invoices as $invoice) {
            foreach ($invoice->shadows as $shadow) {
                Tenancy::setTenant(Company::find($shadow->shadow_company_id));
                $shadowInvoice = Invoice::find($shadow->shadow_id);
                $this->invoicePaymentService->migrateInvoicedPaymentsAmountForSingleInvoice($shadowInvoice);
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
