<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\Country;
use App\Enums\CurrencyCode;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Services\CompanyLegalEntityService;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\Validator;

class PurchaseOrderCreateRequest extends BaseRequest
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
          'delivery_date' => [
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
          'resource_id' => [
              'required',
              'uuid',
          ],
          'manual_input' => [
              'boolean',
              'required',
          ],
          'legal_entity_id' => [
              'nullable',
              'uuid',
              Rule::exists('company_legal_entity', 'legal_entity_id')
                  ->where('company_id', $this->route('company_id')),
          ],
          'payment_terms' => [
              'required',
              'integer',
              'between:1,255',
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
    public function withValidator(Validator $validator)
    {
        $this->checkEuropeanTaxNumber($validator, PurchaseOrder::class);

        $validator->after(function ($validator) {
            $project_id = $this->route('project_id');
            $project = Project::with('order')->findOrFail($project_id);
            if (!$project->purchase_order_project && ($project->order()->doesntExist() || OrderStatus::isDraft($project->order->status))) {
                $validator->errors()->add('order', 'No order or order is still in draft.');
            }

            if (UserRole::isPm_restricted(auth()->user()->role)) {
                if ($project->project_manager_id != auth()->user()->id) {
                    throw new UnauthorizedException();
                }
            }
        });
    }
}
