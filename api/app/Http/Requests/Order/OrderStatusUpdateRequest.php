<?php

namespace App\Http\Requests\Order;

use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Validator;

class OrderStatusUpdateRequest extends BaseRequest
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
          'status' => [
              'required',
              'integer',
              'enum:' . OrderStatus::class,
          ],
          'date' => [
              'nullable',
              'date',
          ],
          'need_invoice' => [
              'boolean',
              'nullable',
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
            if (!($this->status === null)) {
                if ($this->status == OrderStatus::draft()->getIndex() && auth()->user()->super_user) {
                    return;
                }

                $order_id = $this->route('order_id');
                $order = Order::findOrFail($order_id);

                if ($order->shadow) {
                    $validator->errors()->add('order', 'This order is a shadow of an other order. No status updates allowed.');
                    return;
                }

                if ($this->status != OrderStatus::cancelled()->getIndex()) {
                    if (!OrderStatus::isDraft($order->status) && !OrderStatus::isActive($order->status) ) {
                        $validator->errors()->add('status', 'Only status Draft or Active can be updated.');
                    }
                    if (OrderStatus::isDraft($order->status) && !OrderStatus::isActive($this->status)) {
                        $validator->errors()->add('status', 'The status Draft can only be updated to Active.');
                    }
                    if (OrderStatus::isActive($order->status) && !OrderStatus::isDelivered($this->status)) {
                        $validator->errors()->add('status', 'The status Active can only be updated to Delivered.');
                    }
                }

                if ($this->status != OrderStatus::cancelled()->getIndex() && $order->status === OrderStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }
                if ($this->status == OrderStatus::cancelled()->getIndex()) {
                    if (!(UserRole::isOwner(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role))) {
                        $validator->errors()->add('status', 'Only user with owner or admin role can set cancelled status');
                    }
                }
            }
        });
    }
}
