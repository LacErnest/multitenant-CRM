<?php

namespace App\Http\Requests;

use App\Enums\Country;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class BaseRequest extends FormRequest
{
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
          //
        ];
    }

    /**
     * Safe access method for getting all FormRequest input
     *
     * to be used instead of @param array $except
     * @return array
     * @see InteractsWithInput::all() (warning: signature differs)
     *
     */
    public function allSafe($except = []): array
    {
        if (isset($return) && empty($except)) {
            return $return;
        }

        if (empty($except)) {
            static $return = [];
        } else {
            $return = [];
        }

        foreach (array_keys(array_filter($this->rules(), function ($value, $key) use ($except) {
            if (!array_key_exists($key, array_flip($except)) && mb_strpos($key, '.') === false && $this->has($key)) {
                if (!is_array($value)) {
                    return (mb_strpos($value, 'ignore') === false && mb_strpos($value, 'nullable') !== false && ($this->input($key) === null)) || !($this->input($key) === null);
                }

                return (!in_array('ignore', $value) && in_array('nullable', $value) && ($this->input($key) === null)) || !($this->input($key) === null);
            }

            return false;
        }, ARRAY_FILTER_USE_BOTH)) as $array_key) {
            $return[$array_key] = $this->input($array_key);
        }

        return $return;
    }


    /**
     * Configure the validator instance.
     * For checking taxNumber if customer or resource is within the EU.
     * only in case of type freelancer / supplier
     *
     * @param Validator $validator
     * @param $entity
     * @return void
     */
    public function checkEuropeanTaxNumber($validator, $entity)
    {
        $validator->after(function ($validator) use ($entity) {
            $eu_countries = Country::ISEUROPEANCOUNTRY;

            if ($entity === PurchaseOrder::class) {
                if ($this->resource_id) {
                    $resource = Resource::find($this->resource_id);
                    if ($resource && $resource->address->country && in_array($resource->address->country, $eu_countries)
                    && empty($resource->tax_number) && !$resource->non_vat_liable) {
                        $validator->errors()->add('tax_number', 'Tax number is obligated for European resources.
                        Please update the selected resource before creating a purchase order.');
                    }
                }
            } elseif ($entity === Invoice::class) {
                $project = Project::with('contact.customer')->find($this->route('project_id'));

                if ($project && in_array($project->contact->customer->billing_address->country, $eu_countries)
                && empty($project->contact->customer->tax_number) && !$project->contact->customer->non_vat_liable) {
                    $validator->errors()->add('tax_number', 'There is no tax number set for this European customer.');
                }
            } elseif ($entity === Customer::class) {
                if (!in_array($this->billing_country, $eu_countries) && $this->non_vat_liable) {
                    $validator->errors()->add('non_vat_liable', 'Non vat liable can only be set on European customers.');
                }
            } elseif ($entity === Resource::class) {
                if (!in_array($this->country, $eu_countries) && $this->non_vat_liable) {
                    $validator->errors()->add('non_vat_liable', 'Non vat liable can only be set on European resources.');
                }
            }
        });
    }
}
