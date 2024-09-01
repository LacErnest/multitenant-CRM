<?php

namespace App\Http\Requests\Resource;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\PurchaseOrder;
use App\Models\Resource;


class ResourceDownloadInvoiceRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $resourceHasAccessToPO = false;
        $poIsBilled = false;

        $purchase_order_id = $this->route('purchase_order_id');
        $resource_id = $this->route('resource_id');
        $purchaseOrder = PurchaseOrder::find($purchase_order_id);

        if (auth()->user() instanceof Resource) {
            $isLoggedIn = $this->route('resource_id') === auth()->user()->id;
        } else {
            $isLoggedIn = UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
            UserRole::isAdmin(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
            UserRole::isHr(auth()->user()->role) || UserRole::isOwner_read(auth()->user()->role);
        }

        if ($purchaseOrder instanceof PurchaseOrder) {
            $resourceHasAccessToPO = $purchaseOrder->resource_id === $resource_id;
            $poIsBilled = PurchaseOrderStatus::isBilled($purchaseOrder->status) || PurchaseOrderStatus::isPaid($purchaseOrder->status);
        }

        return $isLoggedIn && $resourceHasAccessToPO && $poIsBilled;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [];
    }
}
