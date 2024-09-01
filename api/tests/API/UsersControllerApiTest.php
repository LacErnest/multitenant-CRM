<?php

namespace Tests\API;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Notification;
use Tests\ApiTenantTestCase;

class UsersControllerApiTest extends ApiTenantTestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
  public function testItCreatesUser()
  {
      Notification::fake();
      /** PREPARE */
      $this->actingAsUserWithJWT();
      $user = factory(User::class)->make();

      /** EXECUTE */
      $response = $this->post(route('api.v1.companies.users.create', [
          'company_id' => $this->tenant->id
      ]), [
          'first_name' => $user->first_name,
          'last_name'  => $user->last_name,
          'email'      => $user->email,
          'role'       => UserRole::accountant()->getIndex(),
      ], array_merge($this->authHeaders, [
          'Accept' => 'application/json'
      ]));

      /** ASSERT */
      $response->dump();
      $response->isOk(); // TODO Refactor to 201
      $this->assertEquals($user->email, Arr::get($response, 'email'));
      $this->assertEquals(UserRole::accountant()->getIndex(), Arr::get($response, 'role'));
  }
}
