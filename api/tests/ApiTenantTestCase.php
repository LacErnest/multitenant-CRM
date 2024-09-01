<?php

namespace Tests;

use App\Enums\UserRole;
use App\Models\User;
use Tymon\JWTAuth\Facades\JWTAuth;

abstract class ApiTenantTestCase extends TenantTestCase
{
  protected User $user;

  protected array $authHeaders;

  /**
   * Utility that helps to make a user and authorize him
   * @param null $user
   *
   * @return User
   */
  protected function actingAsUserWithJWT($user = null): User
  {
    if (empty($user)) {
      $user = factory(User::class)->create([
        'company_id' => $this->tenant->id,
        'role' => UserRole::admin()->getIndex(),
        'google2fa' => true
      ]);
    }

    $token = $this->authorize($user);
    $this->authHeaders = ['Authorization' => "Bearer $token"];
    $this->user = $user;

    return $user;
  }

  /**
   * Authorizes user and returns the auth token
   *
   * @param  User $user
   * @return string
   */
  protected function authorize($user): string
  {
    return JWTAuth::fromUser($user);
  }
}
