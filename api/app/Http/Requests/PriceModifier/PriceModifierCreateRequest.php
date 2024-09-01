<?php

namespace App\Http\Requests\PriceModifier;

use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Http\Requests\BaseRequest;
use App\Models\Company;
use App\Services\CompanySettingsService;
use Illuminate\Validation\Validator;

class PriceModifierCreateRequest extends BaseRequest
{
    protected CompanySettingsService $companySettingService;

    public function __construct(CompanySettingsService $companySettingService)
    {
        $this->companySettingService = $companySettingService;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'description' => 'required|string|max:250',
          'type' => 'required|integer|enum:' . PriceModifierType::class,
          'quantity' => 'required|numeric|min:1',
          'quantity_type' => 'required|integer|enum:' . PriceModifierQuantityType::class
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
            $company = Company::find(getTenantWithConnection());
            $setting = $this->companySettingService->view($company->id);
            $config = config('settings.price_modifiers');
            $foundConfigs = array_filter($config, function ($item) {
                return $item['description'] === $this->description;
            });
            if (!empty($foundConfigs)) {
                $descriptionConfig = reset($foundConfigs);
                // quantity type validation
                if (is_array($descriptionConfig['quantity_type']) && !in_array($this->quantity_type, $descriptionConfig['quantity_type'])) {
                    $validator->errors()->add('quantity_type', $this->quantity_type . ' is not a valid quantity_type for this modifier.');
                } elseif (!is_array($descriptionConfig['quantity_type']) && $this->quantity_type != $descriptionConfig['quantity_type']) {
                    $validator->errors()->add('quantity_type', $this->quantity_type . ' is not a valid quantity_type for this modifier.');
                }
              // Type validation
                if ($this->type != $descriptionConfig['type']) {
                    $validator->errors()->add('type', $this->type . ' is not a valid type for this modifier.');
                }
              //Quantity validation
                if ($this->quantity <= 0) {
                    $validator->errors()->add('price_modifiers', $this->quantity . ' is not a valid discount. Must be higher then 0.');
                } elseif ($this->quantity_type == 0 && $this->quantity > $setting->{$descriptionConfig['max_field_value']}) {
                    $validator->errors()->add('price_modifiers', $this->quantity . ' is not a valid percentage. Must not be higher then ' . $descriptionConfig['max_field_value'] . '.');
                }
            } else {
                $validator->errors()->add('description', $this->description . ' is not a valid price modifier.');
            }
        });
    }
}
