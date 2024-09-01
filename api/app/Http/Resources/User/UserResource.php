<?php

namespace App\Http\Resources\User;

use App\Enums\CommissionModel;
use App\Enums\UserRole;
use App\Models\SalesCommission;
use App\Models\User;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $commissionPercent = null;
        $commissionModel = null;
        $secondSale = null;

        if (UserRole::isSales($this->role)) {
            $primaryUser = User::where([['email', $this->email], ['primary_account', true]])->first();
            $salesRecord = $primaryUser->salesCommissions->sortByDesc('created_at')->first();
            $commissionModel = $salesRecord->commission_model ?? CommissionModel::default()->getIndex();

            if (CommissionModel::isLead_generation($commissionModel)) {
                $commissionPercent = $salesRecord->commission ?? SalesCommission::DEFAULT_LEAD_COMMISSION;
                $secondSale = $salesRecord->second_sale_commission ?? SalesCommission::DEFAULT_SECOND_SALE;
            } elseif (CommissionModel::isLead_generationB($commissionModel)) {
                $commissionPercent = $salesRecord->commission ?? SalesCommission::DEFAULT_LEAD_COMMISSION_B;
            } elseif (CommissionModel::isDefault($commissionModel)) {
                $commissionPercent = $salesRecord->commission ?? SalesCommission::DEFAULT_COMMISSION;
            } elseif (CommissionModel::isSales_support($commissionModel)) {
                $commissionPercent = $this->company->sales_support_commission;
            } elseif (CommissionModel::isCustom_modelA($commissionModel)) {
                $commissionPercent = SalesCommission::DEFAULT_SECOND_SALE;
            }
        }

        return
        [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'role' => $this->role,
            'commission_percentage' => $commissionPercent,
            'second_sale_commission' => $secondSale,
            'commission_model' => $commissionModel,
            'password_set' => !($this->password === null),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'disabled_at' => $this->disabled_at,
        ];
    }
}
