<?php

namespace App\Http\Resources\Auth;

use App\Enums\UserRole;
use App\Models\Company;
use Illuminate\Http\Resources\Json\JsonResource;

class LoginResource extends JsonResource
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

        return
          [
              'id' => $this->id,
              'first_name' => $this->first_name,
              'last_name' => $this->last_name,
              'email' => $this->email,
              'google2fa' => $this->google2fa == 1,
              'super_user' => $this->when($this->super_user, true),
              'companies' => CompanyResource::collection($companies),
          ];
    }
}
