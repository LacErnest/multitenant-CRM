<?php

namespace App\Http\Resources\Employee;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

/**
 * Class EmployeeResource
 */
class EmployeeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $hourlyRate = isset($this->salary) && isset($this->working_hours) ?
          round(safeDivide($this->salary, $this->working_hours), 2) :
          null;

        return [
          'id'                => $this->id,
          'first_name'        => $this->first_name,
          'last_name'         => $this->last_name,
          'email'             => $this->email,
          'type'              => $this->type,
          'status'            => $this->status,
          'hourly_rate'       => $hourlyRate,
          'salary'            => $this->salary,
          'linked_in_profile' => $this->linked_in_profile,
          'facebook_profile'  => $this->facebook_profile,
          'started_at'        => $this->started_at,
          'working_hours'     => $this->working_hours,
          'phone_number'      => $this->phone_number,
          'addressline_1'     => $this->address->addressline_1 ?? null,
          'addressline_2'     => $this->address->addressline_2 ?? null,
          'city'              => $this->address->city ?? null,
          'region'            => $this->address->region ?? null,
          'postal_code'       => $this->address->postal_code ?? null,
          'country'           => $this->address->country ?? null,
          'created_at'        => $this->created_at,
          'updated_at'        => $this->updated_at,
          'role'              => $this->role,
          'legal_entity_id'   => $this->legal_entity_id,
          'legal_entity'      => $this->legalEntity ? $this->legalEntity->name : null,
          'legal_country'     => $this->legalEntity ? $this->legalEntity->address->country : null,
          'default_currency'  => $this->default_currency,
          'is_pm'             => $this->is_pm,
          'overhead_employee' => $this->overhead_employee,
          $this->mergeWhen(EmployeeType::isContractor($this->type), [
              'purchase_orders'   => EmployeePurchaseOrderResource::collection($this->purchaseOrders),
          ]),
          'files' => EmployeeFilesResource::collection($this->getMedia('cv')),
        ];
    }
}
