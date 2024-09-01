<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Validator;

class ShareOrderRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        if (UserRole::isSales(auth()->user()->role) || UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role)) {
            return false;
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
          'master' => [
              'boolean',
              'required',
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
            $order_id = $this->route('order_id');
            $order = Order::with('shadows')->findOrFail($order_id);
            if ($order->shadow) {
                $validator->errors()->add('master', 'This action is not valid for shadow order.');
            } elseif ($order->master === $this->master) {
                $validator->errors()->add('master', 'The status of the shared order is invalid.');
            } elseif ($order->master && !$this->master && $order->shadows->count()) {
                $validator->errors()->add('master', 'Order sharing cannot be disabled. It is linked to other companies.');
            }
        });
    }
}
