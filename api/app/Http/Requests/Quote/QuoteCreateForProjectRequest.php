<?php

namespace App\Http\Requests\Quote;

use App\Enums\CurrencyCode;
use App\Enums\DownPaymentAmountType;
use App\Enums\OrderStatus;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\Quote;
use App\Services\CompanyLegalEntityService;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\Support\Arr;

class QuoteCreateForProjectRequest extends BaseRequest
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
          'expiry_date' => [
              'required',
              'date',
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
          'contact_id' => [
              'uuid',
              'exists:contacts,id',
          ],
          'manual_input' => [
              'boolean',
              'required',
          ],
          'down_payment' => [
              'nullable',
              'integer',
          ],
          'down_payment_type' => 'nullable|integer|enum:'. DownPaymentAmountType::class,
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
            $project_id = $this->route('project_id');
            $project = Project::with('contact', 'order')->find($project_id);
            if ($project->order()->where('status', '>=', OrderStatus::delivered()->getIndex())->exists()) {
                $validator->errors()->add('order', 'This project has been delivered. Creating quotes is not allowed.');
                return;
            }

            if ($project->order->shadow) {
                $validator->errors()->add('order', 'This project is a shadow project. Creating new quotes is not allowed.');
                return;
            }

            if (!empty($this->contact_id)) {
                if ($project->order()->exists() && !in_array($this->contact_id, $project->contact->customer->contacts()->pluck('id')->toArray())) {
                    $validator->errors()->add('contact_id', 'An order has been created for this project. Wrong customer contact.');
                    return;
                }
            }
        });
    }
}
