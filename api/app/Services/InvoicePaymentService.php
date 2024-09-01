<?php


namespace App\Services;

use App\Contracts\Repositories\InvoicePaymentRepositoryInterface;
use App\DTO\InvoicePayments\CreateInvoicePaymentDTO;
use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\Setting;
use App\Repositories\InvoicePaymentRepository;
use App\Repositories\InvoiceRepository;
use Carbon\Carbon;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Illuminate\Support\Facades\Cache;

/**
 * InvoicePaymentService
 */
class InvoicePaymentService
{
    protected InvoicePaymentRepository $invoice_payment_repository;

    protected InvoicePaymentRepositoryInterface $invoicePaymentRepository;

    protected InvoiceRepository $invoice_repository;

    public function __construct(
        InvoicePaymentRepository $invoice_payment_repository,
        InvoicePaymentRepositoryInterface $invoicePaymentRepository,
        InvoiceRepository $invoice_repository
    ) {
        $this->invoice_payment_repository = $invoice_payment_repository;
        $this->invoicePaymentRepository = $invoicePaymentRepository;
        $this->invoice_repository = $invoice_repository;
    }

    /**
     * Create new payment for a specific invoice
     * * Payment amount is converted to USD amount
     * @param Invoice $invoice
     * @param CreateInvoicePaymentDTO $createInvoicePaymentDTO
     * @return InvoicePayment
     */
    public function create(Invoice $invoice, CreateInvoicePaymentDTO $createInvoicePaymentDTO): InvoicePayment
    {
        $invoicePayment = $this->createWithoutCheckInvoiceStatus($invoice, $createInvoicePaymentDTO);
        $this->checkAndUpdateInvoiceStatus($invoice);
        return $invoicePayment;
    }

    /**
     * Create new payment for a specific invoice
     * * Payment amount is converted to USD amount
     * @param Invoice $invoice
     * @param CreateInvoicePaymentDTO $createInvoicePaymentDTO
     * @return InvoicePayment
     */
    public function createWithoutCheckInvoiceStatus(Invoice $invoice, CreateInvoicePaymentDTO $createInvoicePaymentDTO): InvoicePayment
    {
        $format = LegalEntitySetting::where('id', $invoice->legalEntity->legal_entity_setting_id)->first();
        $format->invoice_payment_number += 1;
        $paymentNumber = transformFormat($format->invoice_payment_number_format, $format->invoice_payment_number);
        $total_paid = $invoice->payments->sum('pay_amount');
        $invoiceTotalPrice = get_total_price(Invoice::class, $invoice->id);

        if ($invoiceTotalPrice < $total_paid + $createInvoicePaymentDTO->pay_amount) {
            $createInvoicePaymentDTO->pay_amount = $invoiceTotalPrice - $total_paid;
        }

        if ($createInvoicePaymentDTO->pay_full_price) {
            $createInvoicePaymentDTO->pay_amount = $invoiceTotalPrice - $total_paid;
        }

        $data = $createInvoicePaymentDTO->toArray();
        unset($data['pay_full_price']);


        $invoicePayment = $this->invoicePaymentRepository->create(
            array_merge($data, [
                'number' => $paymentNumber,
                'invoice_id' => $invoice->id,
                'currency_code' => $invoice->currency_code,
                'created_by' => auth()->user()->id ?? $invoice->created_by
            ])
        );
        $format->save();
        $invoice->refresh();
        return $invoicePayment;
    }

    /**
     * Update existing invoice payment
     * Payment amount is converted to USD amount
     * @param Invoice $invoice
     * @param BaseRequest $request
     * @return InvoicePayment
     */
    public function update(InvoicePayment $invoicePayment, BaseRequest $request)
    {
        $total_paid = $invoicePayment->invoice->payments->sum('pay_amount');
        $invoiceTotalPrice = get_total_price(Invoice::class, $invoicePayment->invoice->id);
        $pay_amount = $request->get('pay_amount');

        if (!empty($request->pay_full_price) || $invoiceTotalPrice < $total_paid + $pay_amount - $invoicePayment->pay_amount) {
            $pay_amount = $invoiceTotalPrice - $total_paid;
        }

        $invoicePayment =  $this->invoice_payment_repository->update(
            $invoicePayment,
            [
                'currency_code' => $invoicePayment->invoice->currency_code,
                'pay_amount' => $pay_amount
            ]
        );
        $invoicePayment->invoice->refresh();
        $this->checkAndUpdateInvoiceStatus($invoicePayment->invoice);
        return $invoicePayment;
    }


    /**
     * Check connected user authorization
     */
    public function checkAuthorization(): void
    {
        if (
            UserRole::isSales(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
            UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role) ||
            UserRole::isPm_restricted(auth()->user()->role)
        ) {
            throw new UnauthorizedException();
        }
    }

    /**
     * Check invoice status before any payment.
     * @param string invoicePaymentId
     */
    public function checkInvoiceStatus(string $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if (InvoiceStatus::isPaid($invoice->status)) {
            throw new UnprocessableEntityHttpException(
                "Payable invoice, can't update."
            );
        }

        if (!InvoiceStatus::isSubmitted($invoice->status) && !InvoiceStatus::isUnpaid($invoice->status) && !InvoiceStatus::isPartial_paid($invoice->status)) {
            throw new UnprocessableEntityHttpException(
                'Invoice has not been submitted.'
            );
        }
    }


    /**
     * Calculate the total paid amount a specific invoice
     *
     * @param  string $invoiceId
     * @return float
     */
    public function getTotalPaidAmountForInvoice(string $invoiceId): float
    {
        return InvoicePayment::where('invoice_id', $invoiceId)
            ->get()
            ->sum('pay_amount');
    }

    /**
     * Update invoice status if necessery
     */
    private function checkAndUpdateInvoiceStatus(Invoice $invoice): void
    {
        $invoice->refresh();
        $total_paid = ceiling($invoice->payments->sum('pay_amount'), 2);
        $last_payment = $invoice->payments()->orderByDesc('pay_date')->first();
        $invoiceTotalPrice = ceiling(get_total_price(Invoice::class, $invoice->id), 2);

        if ($total_paid === 0) {
            $updateStatusData = [
                'status' => InvoiceStatus::submitted()->getIndex(),
                'pay_date' => null
            ];
        } elseif ($total_paid < $invoiceTotalPrice) {
            $updateStatusData = [
                'status' => InvoiceStatus::partial_paid()->getIndex(),
                'total_paid' => $total_paid,
                'pay_date' => null
            ];
        } else {
            $pay_date = $last_payment->pay_date;
            if (empty($pay_date)) {
                $pay_date = Carbon::now()->format('Y-m-d');
            }
            $updateStatusData = [
                'status' => InvoiceStatus::paid()->getIndex(),
                'total_paid' => $invoiceTotalPrice,
                'pay_date' => $pay_date
            ];
        }

        $this->invoice_repository->update($invoice, $updateStatusData);
    }

    public function migrateInvoicedPaymentsAmount()
    {
        $invoices = Invoice::all();
        foreach ($invoices as $invoice) {
            $this->migrateInvoicedPaymentsAmountForSingleInvoice($invoice);
        }
    }

    public function migrateInvoicedPaymentsAmountForSingleInvoice($invoice)
    {
        $totalPaidAmount = $invoice->payments->sum('pay_amount');
        $totalAmount = 0;
        $invoiceTotalPrice = get_total_price(Invoice::class, $invoice->id);

        if (InvoiceStatus::isPaid($invoice->status)) {
            $totalAmount = $invoiceTotalPrice - $totalPaidAmount;
        } elseif ($invoice->total_paid > $totalPaidAmount) {
            $totalAmount = $invoice->total_paid - $totalPaidAmount;
        }
        if ($totalAmount > 0) {
            $payDate = !empty($invoice->pay_date) ? $invoice->pay_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $dao = new CreateInvoicePaymentDTO([
                'pay_amount' => floatval($totalAmount),
                'pay_date' => $payDate,
                'currency_code' => $invoice->currency_code
            ]);
            $this->create($invoice, $dao);
        }
    }


    public function refreshInvoicedPaymentsAmountForSingleInvoice($invoice)
    {

        if (InvoiceStatus::isPaid($invoice->status)) {
            $invoice->payments()->delete();
            $payDate = !empty($invoice->pay_date) ? $invoice->pay_date->format('Y-m-d') : Carbon::now()->format('Y-m-d');
            $dao = new CreateInvoicePaymentDTO([
                'pay_amount' => get_total_price(Invoice::class, $invoice->id),
                'pay_date' => $payDate,
                'currency_code' => $invoice->currency_code
            ]);
            $this->createWithoutCheckInvoiceStatus($invoice, $dao);
        } elseif (InvoiceStatus::isPartial_paid($invoice->status)) {
            $invoice->payments()->update([
                'currency_code' => $invoice->currency_code
            ]);
            $invoice->refresh();
        }
    }

    public function getInvoiceTotalPaidInEuro($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        $totalInEuro = 0;
        if (!empty($invoice)) {
            $companyCurrency = Company::findOrFail(getTenantWithConnection())->currency_code;
            foreach ($invoice->payments as $payment) {
                $currencyCode = $payment->currency_code != null ? $payment->currency_code : $invoice->currency;
                if ($currencyCode === CurrencyCode::EUR()->getIndex()) {
                    $currencyRate = 1;
                } elseif (CurrencyCode::USD()->getIndex() == $companyCurrency) {
                    $currencyRate = safeDivide($invoice->currency_rate_customer, $invoice->currency_rate_company);
                    $currencyRate = safeDivide(1, $currencyRate);
                } else {
                    $currencyRate = safeDivide(1, $invoice->currency_rate_customer);
                }
                $totalInEuro += $currencyRate * $payment->pay_amount;
            }
        }
        return $totalInEuro;
    }

    public function getInvoiceTotalPaidInUsd($invoiceId)
    {
        $invoice = Invoice::find($invoiceId);
        $totalInUsd = 0;
        if (!empty($invoice)) {
            $companyCurrency = Company::findOrFail(getTenantWithConnection())->currency_code;
            foreach ($invoice->payments as $payment) {
                $currencyCode = $payment->currency_code != null ? $payment->currency_code : $invoice->currency;
                if ($currencyCode === CurrencyCode::USD()->getIndex()) {
                    $currencyRate = 1;
                } elseif (CurrencyCode::EUR()->getIndex() == $companyCurrency) {
                    $currencyRate = safeDivide($invoice->currency_rate_customer, $invoice->currency_rate_company);
                    $currencyRate = safeDivide(1, $currencyRate);
                } else {
                    $currencyRate = safeDivide(1, $invoice->currency_rate_customer);
                }
                $totalInUsd += $currencyRate * $payment->pay_amount;
            }
        }
        return $totalInUsd;
    }

    public function getOrderFullInvoicedAmount($orderId)
    {
        $payments = InvoicePayment::whereHas('invoice', function ($query) use ($orderId) {
            return $query->whereHas('order', function ($query) use ($orderId) {
                return $query->where('orders.id', $orderId);
            });
        })->get();

        $totalInEuro = 0;
        if (!empty($payments)) {
            $companyCurrency = Company::findOrFail(getTenantWithConnection())->currency_code;
            foreach ($payments as $payment) {
                $invoice = $payment->invoice;
                $currencyCode = $payment->currency_code != null ? $payment->currency_code : $invoice->currency;
                if ($currencyCode === CurrencyCode::EUR()->getIndex()) {
                    $currencyRate = 1;
                } elseif (CurrencyCode::USD()->getIndex() == $companyCurrency) {
                    $currencyRate = safeDivide($invoice->currency_rate_customer, $invoice->currency_rate_company);
                    $currencyRate = safeDivide(1, $currencyRate);
                } else {
                    $currencyRate = safeDivide(1, $invoice->currency_rate_customer);
                }
                $totalInEuro += $currencyRate * $payment->pay_amount;
            }
        }
        return $totalInEuro;
    }

    public function deteleInvoicePayment($invoice, $invoicePaymentIds)
    {
        $status = InvoicePayment::destroy($invoicePaymentIds);
        $this->checkAndUpdateInvoiceStatus($invoice);
        return $status;
    }
}
