<?php

namespace App\Http\Resources\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

class TwoFactorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        $isAdmin = UserRole::isAdmin(auth()->user()->role);

        if ($isAdmin) {
            $companies = Company::all();
        } else {
            $whereQuery = function ($query) {
                $query->where('email', auth()->user()->email);
            };
            $companies = Company::whereHas('users', $whereQuery)
            ->with(['users' => $whereQuery])->get();
        }

        return [
          'companies' => CompanyResource::collection($companies)
        ];
    }
}
