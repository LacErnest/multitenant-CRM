<?php


namespace App\Repositories;

use App\Enums\CreditNoteStatus;
use App\Enums\CreditNoteType;
use App\Enums\CurrencyCode;
use App\Jobs\ElasticUpdateAssignment;
use App\Models\CreditNote;
use App\Models\Invoice;
use Illuminate\Support\Facades\Cache;
use XeroAPI\XeroPHP\Models\Accounting\CreditNote as XeroCreditNote;

class CreditNoteRepository
{
    protected CreditNote $credit_note;

    public function __construct(CreditNote $credit_note)
    {
        $this->credit_note = $credit_note;
    }

    public function create($creditNoteXero)
    {
        $rate = getCurrencyRates()['rates'][$creditNoteXero->getCurrencyCode()];
        $rateEURToUSD = getCurrencyRates()['rates']['USD'];
        $invoice_id = null;
        $invoice = Invoice::where('xero_id', $creditNoteXero->getAllocations()[0]->getInvoice()->getInvoiceId())->first();

        if ($invoice) {
            $invoice_id = $invoice->id;
        }

        $this->credit_note->xero_id = $creditNoteXero->getCreditNoteId();
        $this->credit_note->invoice_id = $invoice_id;
        $this->credit_note->project_id = $invoice ? $invoice->project_id : null;
        $this->credit_note->type = CreditNoteType::make($creditNoteXero->getType())->getIndex();
        $this->credit_note->date = convertXeroStringToDate($creditNoteXero->getDate());
        $this->credit_note->status = CreditNoteStatus::make($creditNoteXero->getStatus())->getIndex();
        $this->credit_note->number = $creditNoteXero->getCreditNoteNumber();
        $this->credit_note->reference = $creditNoteXero->getReference();
        $this->credit_note->currency_code = CurrencyCode::make($creditNoteXero->getCurrencyCode())->getIndex();
        $this->credit_note->total_price = $creditNoteXero->getTotal() * $rate;
        $this->credit_note->total_vat = $creditNoteXero->getTotaltax() * $rate;
        $this->credit_note->total_price_usd = safeDivide($this->credit_note->total_price, $rateEURToUSD);
        $this->credit_note->total_vat_usd = safeDivide($this->credit_note->total_vat, $rateEURToUSD);
        $this->credit_note->save();

        ElasticUpdateAssignment::dispatch(getTenantWithConnection(), CreditNote::class, $this->credit_note->id)->onQueue('low');

        return $this->credit_note;
    }

    public function update($creditNoteXero)
    {
        $rate = getCurrencyRates()['rates'][$creditNoteXero->getCurrencyCode()];
        $rateEURToUSD = getCurrencyRates()['rates']['USD'];
        $invoice_id = null;
        $invoice = Invoice::where('xero_id', $creditNoteXero->getAllocations()[0]->getInvoice()->getInvoiceId())->first();

        if ($invoice) {
            $invoice_id = $invoice->id;
        }

        $credit_note = $this->credit_note->where('xero_id', $creditNoteXero->getCreditNoteId())->first();

        if ($creditNoteXero->getStatus() == XeroCreditNote::STATUS_DELETED) {
            return $credit_note->delete();
        }

        $credit_note->xero_id = $creditNoteXero->getCreditNoteId();
        $credit_note->invoice_id = $invoice_id;
        $this->credit_note->project_id = $invoice ? $invoice->project_id : null;
        $credit_note->type = CreditNoteType::make($creditNoteXero->getType())->getIndex();
        $credit_note->date = convertXeroStringToDate($creditNoteXero->getDate());
        $credit_note->status = CreditNoteStatus::make($creditNoteXero->getStatus())->getIndex();
        $credit_note->number = $creditNoteXero->getCreditNoteNumber();
        $credit_note->reference = $creditNoteXero->getReference();
        $credit_note->currency_code = CurrencyCode::make($creditNoteXero->getCurrencyCode())->getIndex();
        $credit_note->total_price = $creditNoteXero->getTotal() * $rate;
        $credit_note->total_vat = $creditNoteXero->getTotaltax() * $rate;
        $credit_note->total_price_usd = safeDivide($credit_note->total_price, $rateEURToUSD);
        $credit_note->total_vat_usd = safeDivide($credit_note->total_vat, $rateEURToUSD);
        $credit_note->save();

        ElasticUpdateAssignment::dispatch(getTenantWithConnection(), CreditNote::class, $credit_note->id)->onQueue('low');

        return $credit_note;
    }
}
