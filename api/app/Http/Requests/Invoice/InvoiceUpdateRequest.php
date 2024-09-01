<?php

namespace App\Http\Requests\Invoice;

use App\Enums\CurrencyCode;
use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Enums\DownPaymentStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InvoiceUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isOwner = UserRole::isOwner(auth()->user()->role);
        $isAdmin = UserRole::isAdmin(auth()->user()->role);
        $isAccountant = UserRole::isAccountant(auth()->user()->role);

        return $isOwner || $isAdmin || $isAccountant;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'date' => [
              'date'
          ],
          'due_date' => [
              'date',
              'after_or_equal:date'
          ],
          'reference' => [
              'string',
              'nullable',
              'max:50'
          ],
          'currency_code' => [
              'integer',
              'enum:' . CurrencyCode::class
          ],
          'manual_input' => [
              'boolean',
              'required'
          ],
          'legal_entity_id' => [
              'required',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
          ],
          'master' => [
              'boolean'
          ],
          'vat_status' => [
              'nullable',
              'integer',
              'enum:' . VatStatus::class,
          ],
          'vat_percentage' => [
              'numeric',
              'nullable',
              'min:0',
              'max:100',
          ],
          'eligible_for_earnout'=>[
              'boolean',
              'required',
          ],
          'down_payment_status' => [
              'nullable',
              'integer',
              'enum:' . DownPaymentStatus::class,
          ],
          'down_payment' => [
              'numeric',
              'nullable',
              'min:0',
              'max:100',
          ],
          'email_template_id' => [
            'nullable',
            'exists:tenant.email_templates,id',
          ],
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
            $invoice_id = $this->route('invoice_id');
            $invoice = Invoice::findOrFail($invoice_id);

            if ($invoice->shadow) {
                $validator->errors()->add('status', 'This invoice is a shadow. No updates allowed.');
                return;
            }

            if ($invoice->status === InvoiceStatus::cancelled()->getIndex()) {
                $validator->errors()->add('status', 'Invoices with the status Cancelled can not be updated.');
                return;
            }
            if (!($this->status === null)) {
                if ($this->status != InvoiceStatus::cancelled()->getIndex() && $invoice->status === InvoiceStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }
                if ($this->status == InvoiceStatus::cancelled()->getIndex()) {
                    if (!UserRole::isAdmin(auth()->user()->role)) {
                        $validator->errors()->add('status', 'Only users with an admin role can update the status to Cancelled.');
                    }
                }
            }
        });
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    public function prepareForValidation()
    {
        $project = Project::find($this->route('project_id'));
        $isIntraCompany = isIntraCompany($project->contact->customer->id ?? null);
        $this->merge([
          'eligible_for_earnout' => !$isIntraCompany || $this->input('eligible_for_earnout'),
        ]);
    }
}
