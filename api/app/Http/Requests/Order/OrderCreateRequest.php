<?php

namespace App\Http\Requests\Order;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Services\CompanyLegalEntityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrderCreateRequest extends BaseRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'project_manager_id' => [
              'nullable',
              'uuid',
              Rule::exists('tenant.employees', 'id')
                  ->where('is_pm', true)
                  ->whereIn('status', [
                      EmployeeStatus::active()->getIndex(),
                      EmployeeStatus::potential()->getIndex(),
                  ]),
          ],
          'date' => [
              'required',
              'date',
          ],
          'deadline' => [
              'required',
              'date',
              'after:' . Carbon::now(),
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
            if (!empty($this->legal_entity_id) && UserRole::isPm(auth()->user()->role)) {
                $companyLegalService = App::make(CompanyLegalEntityService::class);
                if (!$companyLegalService->checkIfValid($this->route('company_id'), $this->legal_entity_id)) {
                    $validator->errors()->add('legal_entity_id', 'You can only pick the default or local legal entity of your company.');
                }
            }
        });
    }
}
