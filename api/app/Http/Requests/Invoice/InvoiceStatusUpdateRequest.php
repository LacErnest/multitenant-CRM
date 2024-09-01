<?php

namespace App\Http\Requests\Invoice;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\CompanyNotificationSetting;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Validation\Validator;

class InvoiceStatusUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isNotSP = !UserRole::isSales(auth()->user()->role);
        $isNotHR = !UserRole::isHr(auth()->user()->role);
        $isNotReadOnly = !UserRole::isOwner_read(auth()->user()->role);

        return $isNotSP && $isNotHR && $isNotReadOnly;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'status' => 'required|integer|enum:' . InvoiceStatus::class,
            'pay_date' => 'date|nullable|required_if:status,' . InvoiceStatus::paid()->getIndex(),
            'notify_client' => 'nullable|boolean|required_if:status,' . InvoiceStatus::submitted()->getIndex(),
            'email_template_id' => 'nullable|exists:tenant.email_templates,id',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            if (!($this->status === null)) {
                if ($this->status == InvoiceStatus::draft()->getIndex() && auth()->user()->super_user) {
                    return;
                }

                $invoice_id = $this->route('invoice_id');
                $invoice = Invoice::findOrFail($invoice_id);

                if ($invoice->shadow) {
                    $validator->errors()->add('status', 'This invoice is a shadow. No updates allowed.');
                    return;
                }

                if ((auth()->user() instanceof User) && UserRole::isAccountant(auth()->user()->role) &&
                    InvoiceStatus::isAuthorised($this->status) && InvoiceType::isAccrec($invoice->type)
                ) {
                    $validator->errors()->add('status', 'You are not allowed to change the status of this invoice to Authorized.');
                    return;
                }

                if (InvoiceType::isAccrec($invoice->type) && InvoiceStatus::isRejected($this->status)) {
                    $validator->errors()->add('status', 'Only resource invoices can be refused.');
                    return;
                }

                if (InvoiceType::isAccrec($invoice->type) && (UserRole::isPm(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role))) {
                    $validator->errors()->add('status', 'You are not allowed to change the status of this invoice.');
                    return;
                }

                if (InvoiceType::isAccpay($invoice->type) && (UserRole::isPm(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role))) {
                    if (!InvoiceStatus::isApproval($this->status) && !InvoiceStatus::isRejected($this->status)) {
                        $validator->errors()->add('status', 'You can only approve or refuse this invoice.');
                        return;
                    }
                }
                if (InvoiceType::isAccpay($invoice->type) && $this->status != InvoiceStatus::cancelled()->getIndex()) {
                    if (InvoiceStatus::isDraft($invoice->status) && !InvoiceStatus::isApproval($this->status) && !InvoiceStatus::isRejected($this->status)) {
                        $validator->errors()->add('status', 'The status Draft can only be updated to Approved or Refused.');
                    }
                    if (InvoiceStatus::isApproval($invoice->status) && !InvoiceStatus::isAuthorised($this->status) && !InvoiceStatus::isRejected($this->status)) {
                        $validator->errors()->add('status', 'The status Approval can only be updated to Authorised or Refused.');
                    }
                }

                if ($this->status != InvoiceStatus::cancelled()->getIndex()) {
                    if (InvoiceStatus::isPaid($invoice->status)) {
                        $validator->errors()->add('status', "Invoices with status Paid can't be updated.");
                    }
                    if (InvoiceType::isAccrec($invoice->type) && InvoiceStatus::isDraft($invoice->status) && !InvoiceStatus::isApproval($this->status)) {
                        $validator->errors()->add('status', 'The status Draft can only be updated to Approval.');
                    }
                    if (InvoiceType::isAccrec($invoice->type) && InvoiceStatus::isApproval($invoice->status) && !InvoiceStatus::isAuthorised($this->status)) {
                        $validator->errors()->add('status', 'The status Approval can only be updated to Authorised.');
                    }
                    if (InvoiceStatus::isAuthorised($invoice->status) && !InvoiceStatus::isSubmitted($this->status)) {
                        $validator->errors()->add('status', 'The status Authorised can only be updated to Submitted.');
                    }
                    if (InvoiceStatus::isSubmitted($invoice->status) && (!InvoiceStatus::isPaid($this->status) && !InvoiceStatus::isUnpaid($this->status))) {
                        $validator->errors()->add('status', 'The status Submitted can only be updated to Paid or Unpaid.');
                    }
                    if (InvoiceStatus::isUnpaid($invoice->status) && !InvoiceStatus::isPaid($this->status)) {
                        $validator->errors()->add('status', 'The status Unpaid can only be updated to Paid.');
                    }
                }

                if ($this->status != InvoiceStatus::cancelled()->getIndex() && $invoice->status === InvoiceStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }

                if ($this->status == InvoiceStatus::cancelled()->getIndex()) {
                    if (!UserRole::isAdmin(auth()->user()->role)) {
                        $validator->errors()->add('status', 'Only users with an admin role can update the status to Cancelled.');
                    }
                }

                if ($this->status != InvoiceStatus::rejected()->getIndex() && $invoice->status === InvoiceStatus::rejected()->getIndex()) {
                    $validator->errors()->add('status', 'The status Refused can not be updated to another status.');
                }

                if ($this->status == InvoiceStatus::submitted()->getIndex()) {
                    $companyNotificationSettings = CompanyNotificationSetting::find(getTenantWithConnection());
                    $emailTemplateGloballyDisabled = $companyNotificationSettings->globally_disabled_email ?? false;
                    if (!$emailTemplateGloballyDisabled && $this->notify_client &&  empty($this->email_template_id)) {
                        $validator->errors()->add('email_template_id', 'The email template is required.');
                    }
                }
            }
        });
    }
}
