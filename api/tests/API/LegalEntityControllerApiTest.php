<?php

namespace Tests\API;

use App\Models\CustomerAddress;
use App\Models\LegalEntity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Tests\ApiTenantTestCase;

/**
 * @group legal-entity
 * @group legal-entity-api-controller-test
 */
class LegalEntityControllerApiTest extends ApiTenantTestCase
{
  public function testItGetSingleLegalEntity()
  {
      /** PREPARE */
      $this->actingAsUserWithJWT();
      $legalEntity = factory(LegalEntity::class)->create();

      /** EXECUTE */
      $response = $this->get(route('api.v1.companies.legal_entities.view', [
          'company_id' => $this->tenant->id,
          'legal_entity_id' => $legalEntity->id,
      ]), $this->authHeaders);

      /** ASSERT */
      $response->dump();
      $response->isOk();
      $this->assertEquals($legalEntity->name, Arr::get($response, 'name'));
      $this->assertEquals($legalEntity->vat_number, Arr::get($response, 'vat_number'));
      $this->assertEquals($legalEntity->swift, Arr::get($response, 'swift'));
      $this->assertEquals($legalEntity->bic, Arr::get($response, 'bic'));
      $this->assertEquals($legalEntity->address_id, Arr::get($response, 'legal_entity_address.id'));
      $this->assertEquals($legalEntity->bank_address_id, Arr::get($response, 'bank_address.id'));
  }

  public function testItCreateLegalEntity()
  {
      /** PREPARE */
      $this->actingAsUserWithJWT();
      $legalEntity = factory(LegalEntity::class)->make();
      $address = factory(CustomerAddress::class)->make();
      $legalEntity->legal_entity_address = $address->toArray();
      $bankAddress = factory(CustomerAddress::class)->make();
      $legalEntity->bank_address = $bankAddress->toArray();

      /** EXECUTE */
      $response = $this->post(route('api.v1.companies.legal_entities.create', [
          'company_id' => $this->tenant->id,
      ]), $legalEntity->toArray(), array_merge($this->authHeaders, [
          'Accept' => 'application/json'
      ]));

      /** ASSERT */
      $response->dump();
      $response->assertCreated();
      $this->assertEquals($legalEntity->name, Arr::get($response, 'name'));
      $this->assertEquals($legalEntity->vat_number, Arr::get($response, 'vat_number'));
      $this->assertEquals($legalEntity->swift, Arr::get($response, 'swift'));
      $this->assertEquals($legalEntity->bic, Arr::get($response, 'bic'));
      $this->assertEquals($legalEntity->legal_entity_address['country'], Arr::get($response, 'legal_entity_address.country'));
      $this->assertEquals($legalEntity->bank_address['country'], Arr::get($response, 'bank_address.country'));
  }

  public function testItUpdateLegalEntity()
  {
      /** PREPARE */
      $this->actingAsUserWithJWT();
      $legalEntity = factory(LegalEntity::class)->create();
      $legalEntity->name = 'update';
      $address = factory(CustomerAddress::class)->make();
      $legalEntity->legal_entity_address = $address->toArray();
      $bankAddress = factory(CustomerAddress::class)->make();
      $legalEntity->bank_address = $bankAddress->toArray();

      /** EXECUTE */
      $response = $this->patch(route('api.v1.companies.legal_entities.update', [
          'company_id' => $this->tenant->id,
          'legal_entity_id' => $legalEntity->id,
      ]), $legalEntity->toArray(), array_merge($this->authHeaders, [
          'Accept' => 'application/json'
      ]));

      /** ASSERT */
      $response->dump();
      $response->isOk();
      $this->assertEquals($legalEntity->name, Arr::get($response, 'name'));
      $this->assertEquals($legalEntity->vat_number, Arr::get($response, 'vat_number'));
      $this->assertEquals($legalEntity->swift, Arr::get($response, 'swift'));
      $this->assertEquals($legalEntity->bic, Arr::get($response, 'bic'));
      $this->assertEquals($legalEntity->legal_entity_address['country'], Arr::get($response, 'legal_entity_address.country'));
      $this->assertEquals($legalEntity->bank_address['country'], Arr::get($response, 'bank_address.country'));
  }

  public function testItDeleteLegalEntity()
  {
      /** PREPARE */
      $this->actingAsUserWithJWT();
      $legalEntity = factory(LegalEntity::class)->create();

      /** EXECUTE */
      $response = $this->delete(route('api.v1.companies.legal_entities.delete', [
          'company_id' => $this->tenant->id,
          'legal_entity_id' => $legalEntity->id,
      ]), [], $this->authHeaders);

      /** ASSERT */
      $response->dump();
      $response->isEmpty();
      $this->assertSoftDeleted((new LegalEntity)->getTable(), [
          'id' => $legalEntity->id
      ]);
  }
}
