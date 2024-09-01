<?php

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Models\Company;
use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class UpdateNullCloseDateOnShadowInvoicesTable extends Migration
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

        $companyId = getTenantWithConnection();

        foreach ($invoices as $invoice) {
            $closeDate = $invoice->close_date;
            if (empty($closeDate)) {
                $closeDate = $invoice->pay_date ?? $invoice->updated_at;
                $invoice->update(['close_date' => $closeDate]);
            }

            if ($invoice->shadows->count() > 0 && !$invoice->master) {
                $invoice->update(['master' => true]);
            } else if (!empty(!$invoice->masterInvoice) &&  !$invoice->shadow) {
                $invoice->update(['shadow' => true]);
            }

            foreach ($invoice->shadows as $shadow) {
                Tenancy::setTenant(Company::find($shadow->shadow_company_id));
                $shadowInvoice = Invoice::find($shadow->shadow_id);
                $attributes = ['close_date' => $closeDate];
                if (empty($shadowInvoice->pay_date)) {
                    $attributes['pay_date'] = $closeDate;
                }
                $shadowInvoice->update($attributes);
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
