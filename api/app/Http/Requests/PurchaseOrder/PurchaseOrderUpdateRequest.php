<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\CurrencyCode;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Enums\VatStatus;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use Illuminate\Validation\Rule;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\Validator;

class PurchaseOrderUpdateRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $isNotSP = !UserRole::isSales(auth()->user()->role);
        $isNotHR = !UserRole::isHr(auth()->user()->role);
        $isNotReadOnly = !UserRole::isOwner_read(auth()->user()->role);

        return $isNotHR && $isNotSP && $isNotReadOnly;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'date' => [
              'date'
          ],
          'delivery_date' => [
              'date',
              'after_or_equal:date'
          ],
          'reference' => [
              'string',
              'nullable',
              'max:50'
          ],
          'currency_code' => [
              'integer',
              'enum:' . CurrencyCode::class
          ],
          'resource_id' => [
              'required',
              'uuid',
          ],
          'manual_input' => [
              'boolean',
              'required'
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
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $purchaseOrder = PurchaseOrder::with('project', 'project.order')->findOrFail($this->route('purchase_order_id'));
            if (!PurchaseOrderStatus::isDraft($purchaseOrder->status)) {
                $validator->errors()->add('status', 'Only purchase orders with status Draft can be edited.');
                return;
            }
            if (!$purchaseOrder->project->purchase_order_project && ($purchaseOrder->project->order()->doesntExist() || OrderStatus::isDraft($purchaseOrder->project->order->status))) {
                $validator->errors()->add('order', 'No order or order is still in draft.');
                return;
            }
            if (!empty($this->legal_entity_id) && (UserRole::isPm(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role)) &&
              $this->legal_entity_id != $purchaseOrder->legal_entity_id) {
                $validator->errors()->add('legal_entity_id', 'You are not allowed to set the legal entity.');
                return;
            }
            if (!empty($this->status)) {
                if ($this->status != PurchaseOrderStatus::cancelled()->getIndex() && $purchaseOrder->status == PurchaseOrderStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }

                if ($this->status == PurchaseOrderStatus::cancelled()->getIndex()) {
                    if (!(UserRole::isOwner(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role))) {
                        $validator->errors()->add('status', 'Only user with owner or admin role can set cancelled status');
                    }
                }
            }
            if (UserRole::isPm_restricted(auth()->user()->role)) {
                if ($purchaseOrder->project->project_manager_id != auth()->user()->id) {
                    throw new UnauthorizedException();
                }
            }
        });
    }
}
