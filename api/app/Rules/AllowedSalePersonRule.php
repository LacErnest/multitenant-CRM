<?php

namespace App\Rules;

use App\Models\CompanySetting;
use App\Models\SalesCommissionPercentage;
use Illuminate\Contracts\Validation\Rule;

class AllowedSalePersonRule implements Rule
{
    /**
     * @var string
     */
    private $companyId;
    /**
     * @var string
     */
    private $orderId;
    /**
     * @var string
     */
    private $invoiceId;
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($companyId, $orderId, $invoiceId)
    {
        $this->companyId = $companyId;
        $this->orderId = $orderId;
        $this->invoiceId = $invoiceId;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $setting = CompanySetting::where('company_id', $this->companyId)->first();
        if (empty($this->orderId) || empty($this->invoiceId)) {
            return false;
        }
        if ($setting) {
            $count_sales_person_commisions = SalesCommissionPercentage::where('sales_person_id', $value)
            ->where('order_id', $this->orderId)
            ->where('invoice_id', $this->invoiceId)->count();
            if ($count_sales_person_commisions >= $setting->sales_person_commission_limit) {
                return false;
            }
        }
        return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        if (empty($this->orderId) || empty($this->invoiceId)) {
            return 'Invalid order or invoice number.';
        }

        return 'the maximum number of commissions for this sales person has already been reached for this order.';
    }
}
