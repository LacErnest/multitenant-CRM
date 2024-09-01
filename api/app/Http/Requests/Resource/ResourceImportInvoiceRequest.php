<?php

namespace App\Http\Requests\Resource;

use App\Enums\Country;
use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Http\Requests\BaseRequest;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Services\EmployeeService;
use App\Services\ResourceService;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\Validator;


class ResourceImportInvoiceRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        $resourceHasAccessToPO = false;
        $poIsAuthorised = false;
        $poIsBilled = false;

        if ((auth()->user() instanceof Resource)) {
            $isLoggedIn = $this->route('resource_id') === auth()->user()->id;
        } else {
            $isLoggedIn = UserRole::isOwner(auth()->user()->role) || UserRole::isAccountant(auth()->user()->role) ||
            UserRole::isAdmin(auth()->user()->role) || UserRole::isPm(auth()->user()->role) ||
            UserRole::isHr(auth()->user()->role) || UserRole::isPm_restricted(auth()->user()->role);
        }

        $purchase_order_id = $this->route('purchase_order_id');
        $resource_id = $this->route('resource_id');
        $purchaseOrder = PurchaseOrder::with('resource')->find($purchase_order_id);
        $this->purchaseOrder = $purchaseOrder;

        if ($purchaseOrder instanceof PurchaseOrder) {
            $resourceHasAccessToPO = $purchaseOrder->resource_id === $resource_id;
            $poIsAuthorised = PurchaseOrderStatus::isAuthorised($purchaseOrder->status);
            $poIsBilled = PurchaseOrderStatus::isBilled($purchaseOrder->status);
        }

        return $isLoggedIn && $resourceHasAccessToPO && ($poIsAuthorised || $poIsBilled);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
          'file' => 'required|base64file|base64mimes:pdf',
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
            $eu_countries = Country::ISEUROPEANCOUNTRY;
            $resourceService = App::make(ResourceService::class);
            $resource = $resourceService->findBorrowedResource($this->purchaseOrder->resource_id);

            if ($resource && !$resource->non_vat_liable && $resource->country && in_array($resource->country, $eu_countries) && empty($resource->tax_number)) {
                $validator->errors()->add('tax_number', 'VAT number is obligated for European resources.');
            }
        });
    }
}
