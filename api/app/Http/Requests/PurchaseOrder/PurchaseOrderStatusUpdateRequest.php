<?php

namespace App\Http\Requests\PurchaseOrder;

use App\Enums\EntityPenaltyType;
use App\Enums\OrderStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Rules\PenaltyRule;
use Illuminate\Validation\UnauthorizedException;
use Illuminate\Validation\Validator;

class PurchaseOrderStatusUpdateRequest extends BaseRequest
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
          'status' => 'integer|required|enum:' . PurchaseOrderStatus::class,
          'penalty' => ['nullable','numeric', new PenaltyRule(request('penalty_type'))],
          'penalty_type' => 'nullable|integer|enum:'. EntityPenaltyType::class,
          'reason_of_penalty' => 'string|max:256|nullable|required_with:penalty',
          'reason_of_rejection' => 'string|nullable|max:256|required_if:status,'.PurchaseOrderStatus::rejected()->getIndex(),
          'pay_date' => 'date|nullable|required_if:status,'.PurchaseOrderStatus::paid()->getIndex(),
          'rating' => 'numeric|nullable|min:1|max:5',
          'reason' => 'string|nullable|max:1000',
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
                if ($this->status == PurchaseOrderStatus::draft()->getIndex() && auth()->user()->super_user) {
                    return;
                }

                $purchaseOrder = PurchaseOrder::with('project', 'project.order')->findOrFail($this->route('purchase_order_id'));
                if (!$purchaseOrder->project->purchase_order_project && ($purchaseOrder->project->order()->doesntExist() || OrderStatus::isDraft($purchaseOrder->project->order->status))) {
                    $validator->errors()->add('order', 'No order or order is still in draft.');
                    return;
                }

                if ($this->status != PurchaseOrderStatus::cancelled()->getIndex()) {
                    if (!$purchaseOrder->legal_entity_id) {
                        $validator->errors()->add('status', 'Legal entity not set.');
                        return;
                    }
                    if (PurchaseOrderStatus::isDraft($purchaseOrder->status) && !PurchaseOrderStatus::isSubmitted($this->status)) {
                        $validator->errors()->add('status', 'The status Draft can only be updated to Submitted.');
                    } elseif (PurchaseOrderStatus::isSubmitted($purchaseOrder->status) && !PurchaseOrderStatus::isCompleted($this->status)) {
                        $validator->errors()->add('status', 'The status Submitted can only be updated to Rejected or Authorized.');
                    } elseif (PurchaseOrderStatus::isCompleted($purchaseOrder->status) && (!PurchaseOrderStatus::isRejected($this->status) && !PurchaseOrderStatus::isAuthorised($this->status))) {
                        $validator->errors()->add('status', 'The status Submitted can only be updated to Rejected or Authorized.');
                    } elseif (PurchaseOrderStatus::isBilled($purchaseOrder->status) && !PurchaseOrderStatus::isPaid($this->status)) {
                        $validator->errors()->add('status', 'The status Billed can only be updated to Paid');
                    } elseif (PurchaseOrderStatus::isRejected($purchaseOrder->status)) {
                        $validator->errors()->add('status', "Rejected purchase orders can't be edited.");
                    } elseif (PurchaseOrderStatus::isAuthorised($purchaseOrder->status)) {
                        $validator->errors()->add('status', "Authorized purchase orders can't be edited.");
                    } elseif (PurchaseOrderStatus::isPaid($purchaseOrder->status)) {
                        $validator->errors()->add('status', "Paid purchase orders can't be edited.");
                    }
                }

                if ($this->status != PurchaseOrderStatus::cancelled()->getIndex() && $purchaseOrder->status === PurchaseOrderStatus::cancelled()->getIndex()) {
                    $validator->errors()->add('status', 'The status Cancelled can not be updated to another status.');
                }

                if ($this->status == PurchaseOrderStatus::cancelled()->getIndex()) {
                    if (!(UserRole::isOwner(auth()->user()->role) || UserRole::isAdmin(auth()->user()->role))) {
                        $validator->errors()->add('status', 'Only user with owner or admin role can set cancelled status');
                    }
                }

                if (UserRole::isPm_restricted(auth()->user()->role)) {
                    if ($purchaseOrder->project->project_manager_id != auth()->user()->id) {
                        throw new UnauthorizedException();
                    }
                }
            }
        });
    }
}
