<?php

namespace App\Http\Requests\Item;

use App\Enums\PriceModifierQuantityType;
use App\Enums\PriceModifierType;
use App\Http\Requests\BaseRequest;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use App\Rules\ServiceRule;
use Illuminate\Validation\Validator;

class ItemCreateRequest extends BaseRequest
{
    /**
     * @var string
     */
    protected $class;
    /**
     * @var string
     */
    protected $entityId;
    /**
     * @var string
     */
    protected $tenantId;
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->route('quote_id')) {
            $this->class = Quote::class;
            $this->entityId = $this->route('quote_id');
        } elseif ($this->route('order_id')) {
            $this->class = Order::class;
            $this->entityId = $this->route('order_id');
        } elseif ($this->route('invoice_id')) {
            $this->class = Invoice::class;
            $this->entityId = $this->route('invoice_id');
        } elseif ($this->route('purchase_order_id')) {
            $this->class = PurchaseOrder::class;
            $this->entityId = $this->route('purchase_order_id');
        }
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $this->tenantId = $this->input('company_id');

        return [
            'service_id' => [
                'nullable',
                new ServiceRule($this->class, $this->entityId, $this->input('service_id'), $this->tenantId),
            ],
            'service_name' => [
                'string',
                'max:250',
                'required',
            ],
            'description' => [
                'string',
                'max:250',
                'nullable',
            ],
            'quantity' => [
                'required',
                'numeric',
                'min:0',
            ],
            'unit' => [
                'required',
                'string',
                'max:25',
            ],
            'unit_price' => [
                'required',
                'numeric',
                'regex:/^\d*(\.\d{1,2})?$/',
            ],
            'order' => [
                'integer',
                'min:0',
                'required',
            ],
            'price_modifiers' => [
                'array',
            ],
            'price_modifiers.*' => [
                'array',
            ],
            'price_modifiers.*.description' => [
                'required_with:price_modifiers.*.type,price_modifiers.*.quantity,price_modifiers.*.quantity_type',
                'string',
                'max:255',
            ],
            'price_modifiers.*.type' => [
                'required_with:price_modifiers.*.description,price_modifiers.*.quantity,price_modifiers.*.quantity_type',
                'integer',
                'enum:' . PriceModifierType::class,
            ],
            'price_modifiers.*.quantity' => [
                'required_with:price_modifiers.*.type,price_modifiers.*.quantity_type,price_modifiers.*.description',
                'numeric',
                'min:1',
            ],
            'price_modifiers.*.quantity_type' => [
                'required_with:price_modifiers.*.type,price_modifiers.*.quantity,price_modifiers.*.description',
                'integer',
                'enum:' . PriceModifierQuantityType::class,
            ],
            'company_id' => [
                'nullable',
                'exists:companies,id',
            ],
            'exclude_from_price_modifiers' => [
                'required',
                'boolean'
            ]
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
            if (!empty($this->price_modifiers)) {
                $config = config('settings.price_modifiers');
                foreach ($this->price_modifiers as $modifier) {
                    if ($modifier['description'] == $config[0]['description']) {
                        if ($modifier['quantity_type'] == 0) {
                            if (!in_array($modifier['quantity'], [5, 10, 15])) {
                                $validator->errors()->add('price_modifiers', $modifier['quantity'] . ' is not a valid percentage. Must be 5, 10 or 15.');
                            }
                        } else {
                            if ($modifier['quantity'] <= 0) {
                                  $validator->errors()->add('price_modifiers', $modifier['quantity'] . ' is not a valid discount. Must be higher then 0.');
                            }
                            $entity = $this->class::findOrFail($this->entityId);
                            if ($entity->master) {
                                  $validator->errors()->add('price_modifiers', 'Only a percentage discount is allowed on shared entities.');
                            }
                        }
                        if ($modifier['type'] != $config[0]['type']) {
                            $validator->errors()->add('type', $modifier['type'] . ' is not a valid type for this modifier.');
                        }
                    } elseif ($modifier['description'] == $config[1]['description']) {
                        if ($modifier['quantity'] > 100) {
                            $validator->errors()->add('price_modifiers', $modifier['quantity'] . ' is not a valid percentage.');
                        }
                        if ($modifier['type'] != $config[1]['type']) {
                              $validator->errors()->add('type', $modifier['type'] . ' is not a valid type for this modifier.');
                        }
                        if ($modifier['quantity_type'] != $config[1]['quantity_type']) {
                                $validator->errors()->add('quantity_type', $modifier['quantity_type'] . ' is not a valid quantity_type for this modifier.');
                        }
                    } elseif ($modifier['description'] == $config[2]['description']) {
                        if ($modifier['quantity'] > 100) {
                            $validator->errors()->add('price_modifiers', $modifier['quantity'] . ' is not a valid percentage.');
                        }
                        if ($modifier['type'] != $config[2]['type']) {
                            $validator->errors()->add('type', $modifier['type'] . ' is not a valid type for this modifier.');
                        }
                        if ($modifier['quantity_type'] != $config[2]['quantity_type']) {
                            $validator->errors()->add('quantity_type', $modifier['quantity_type'] . ' is not a valid quantity_type for this modifier.');
                        }
                    } elseif ($modifier['description'] == $config[3]['description']) {
                        if ($modifier['quantity'] > 100) {
                            $validator->errors()->add('price_modifiers', $modifier['quantity'] . ' is not a valid percentage.');
                        }
                        if ($modifier['type'] != $config[3]['type']) {
                            $validator->errors()->add('type', $modifier['type'] . ' is not a valid type for this modifier.');
                        }
                        if ($modifier['quantity_type'] != $config[3]['quantity_type']) {
                            $validator->errors()->add('quantity_type', $modifier['quantity_type'] . ' is not a valid quantity_type for this modifier.');
                        }
                    } else {
                        $validator->errors()->add('price_modifiers', $modifier['description'] . ' is not a valid price modifier.');
                    }
                }
            }
        });
    }
}
