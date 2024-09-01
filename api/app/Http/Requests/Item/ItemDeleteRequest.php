<?php

namespace App\Http\Requests\Item;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemDeleteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if ($this->route('quote_id')) {
            $this->entityId = $this->route('quote_id');
        } elseif ($this->route('order_id')) {
            $this->entityId = $this->route('order_id');
        } elseif ($this->route('invoice_id')) {
            $this->entityId = $this->route('invoice_id');
        } elseif ($this->route('purchase_order_id')) {
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
        return [
          'items' => [
              'array',
              'required',
              'distinct',
              Rule::exists('tenant.items', 'id')->where('entity_id', $this->entityId),
          ],
        ];
    }
}
