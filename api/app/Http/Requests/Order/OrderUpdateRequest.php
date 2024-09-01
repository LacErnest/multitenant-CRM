<?php

namespace App\Http\Requests\Order;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeStatus;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Order;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OrderUpdateRequest extends BaseRequest
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
          'project_manager_id' => [
              'nullable',
              'uuid',
              Rule::exists('users', 'id')
                  ->whereIn('role', [
                      UserRole::admin()->getIndex(),
                      UserRole::owner()->getIndex(),
                      UserRole::pm()->getIndex(),
                      UserRole::pm_restricted()->getIndex()
                  ]),
          ],
          'date' => [
              'date',
          ],
          'deadline' => [
              'date',
          ],
          'reference' => [
              'string',
              'nullable',
              'max:50',
          ],
          'currency_code' => [
              'integer',
              'enum:' . CurrencyCode::class,
          ],
          'manual_input' => [
              'boolean',
              'required',
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
            $order_id = $this->route('order_id');
            $order = Order::findOrFail($order_id);

            if ($order->status === OrderStatus::cancelled()->getIndex()) {
                $validator->errors()->add('status', 'Orders with the status Cancelled can not be updated.');
                return;
            }
            if (!($this->status === null)) {
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
