<?php

namespace App\Http\Requests\Invoice;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Enums\DownPaymentStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class InvoiceCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'date' => [
              'required',
              'date',
          ],
          'due_date' => [
              'date',
              'required',
              'after_or_equal:date',
          ],
          'reference' => [
              'string',
              'nullable',
              'max:50',
          ],
          'currency_code' => [
              'integer',
              'required',
              'enum:' . CurrencyCode::class,
          ],
          'manual_input' => [
              'boolean',
              'required',
          ],
          'legal_entity_id' => [
              'required',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
          ],
          'master' => [
              'bool',
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
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param Validator $validator
     * @return void
     */
    public function withValidator(Validator $validator)
    {
        $this->checkEuropeanTaxNumber($validator, Invoice::class);
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
