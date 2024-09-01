<?php

namespace App\Http\Resources\Resource;

use App\Enums\PurchaseOrderStatus;
use App\Enums\UserRole;
use App\Http\Resources\Project\ProjectPurchaseOrdersResource;
use App\Http\Resources\PurchaseOrder\ResourcePurchaseOrderResource;
use App\Http\Resources\XeroEntity\XeroEntityStorageResource;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\Resource;
use App\Services\PurchaseOrderService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\App;

class ResourceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {


        if (auth()->user() instanceof Resource) {
            $purchaseOrders = getPurchaseOrders($this->id);
        } elseif (UserRole::isPm_restricted(auth()->user()->role)) {
            $projectIds = Project::where('project_manager_id', auth()->user()->id)->pluck('id')->toArray();
            $purchaseOrders = $this->purchaseOrders->whereIn('project_id', $projectIds);
        } else {
            $purchaseOrders = $this->purchaseOrders;
        }

        return
          [
              'id'               => $this->id,
              'name'             => $this->name,
              'first_name'       => $this->first_name,
              'last_name'        => $this->last_name,
              'email'            => $this->email,
              'type'             => $this->type,
              'status'           => $this->status,
              'tax_number'       => $this->tax_number,
              'default_currency' => $this->default_currency,
              'phone_number'     => $this->phone_number,
              'addressline_1'    => $this->address->addressline_1 ?? null,
              'addressline_2'    => $this->address->addressline_2 ?? null,
              'city'             => $this->address->city ?? null,
              'region'           => $this->address->region ?? null,
              'postal_code'      => $this->address->postal_code ?? null,
              'country'          => $this->address->country ?? null,
              'hourly_rate'      => $this->hourly_rate,
              'daily_rate'       => $this->daily_rate,
              'average_rating'   => $this->average_rating,
              'job_title'        => $this->job_title,
              'created_at'       => $this->created_at,
              'updated_at'       => $this->updated_at,
              'purchase_orders'  => ResourcePurchaseOrderResource::collection($purchaseOrders),
              'contract_file'    => $this->getMedia('contract')->first()->file_name ?? null,
              'legal_entity_id'  => $this->legal_entity_id,
              'xero_id'          => XeroEntityStorageResource::collection($this->xeroEntities),
              'legal_entity'     => $this->legalEntity ? $this->legalEntity->name : null,
              'legal_country'    => $this->legalEntity ? $this->legalEntity->address->country : null,
              'services'         => [
                  'data'  => ResourceServiceResource::collection($this->services),
                  'count' => $this->services->count(),
              ],
              'non_vat_liable'    => $this->non_vat_liable,
          ];
    }
}
