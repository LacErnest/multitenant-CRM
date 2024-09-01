<?php

namespace Tests\Feature;

use App\Models\Bank;
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
        $europenBankAddress = factory(Bank::class)->make();
        $americanBankAddress = factory(Bank::class)->make();
        $legalEntity->address->fill($address->toArray());
        $legalEntity->europeanBank->load('address')->fill($europenBankAddress->toArray());
        $legalEntity->americanBank->load('address')->fill($americanBankAddress->toArray());
        $legalEntityData = $legalEntity->toArray();
        $legalEntityData['european_bank']['bank_address'] = $legalEntityData['european_bank']['address'];
        $legalEntityData['american_bank']['bank_address'] = $legalEntityData['american_bank']['address'];
        $legalEntityData['legal_entity_address'] = Arr::except($legalEntityData['address'], ['created_at', 'updated_at', 'id']);
        $legalEntityData['european_bank']['bank_address'] = Arr::except($legalEntityData['european_bank']['address'], ['created_at', 'updated_at', 'id']);
        $legalEntityData['american_bank']['bank_address'] = Arr::except($legalEntityData['american_bank']['address'], ['created_at', 'updated_at', 'id']);

        unset($legalEntityData['european_bank']['address']);
        unset($legalEntityData['american_bank']['address']);
        unset($legalEntityData['address']);


        /** EXECUTE */
        $response = $this->post(route('api.v1.companies.legal_entities.create', [
            'company_id' => $this->tenant->id,
        ]), $legalEntityData, array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        /** ASSERT */
        $response->assertCreated();
        $this->assertEquals(Arr::get($legalEntityData, 'name'), Arr::get($response->Json(), 'name'));
        $this->assertEquals(Arr::get($legalEntityData, 'vat_number'), Arr::get($response->Json(), 'vat_number'));
        $this->assertEquals(Arr::get($legalEntityData, 'european_bank.bank_address.swift'), Arr::get($response->Json(), 'european_bank.bank_address.swift'));
        $this->assertEquals(Arr::get($legalEntityData, 'european_bank.bank_address.bic'), Arr::get($response->Json(), 'european_bank.bank_address.bic'));
        $this->assertEquals(Arr::get($legalEntityData, 'european_bank.bank_address.country'), Arr::get($response->Json(), 'european_bank.bank_address.country'));
        $this->assertEquals(Arr::get($legalEntityData, 'american_bank.bank_address.swift'), Arr::get($response->Json(), 'american_bank.bank_address.swift'));
        $this->assertEquals(Arr::get($legalEntityData, 'american_bank.bank_address.bic'), Arr::get($response->Json(), 'american_bank.bank_address.bic'));
        $this->assertEquals(Arr::get($legalEntityData, 'american_bank.bank_address.country'), Arr::get($response->Json(), 'american_bank.bank_address.country'));
        $this->assertEquals(Arr::get($legalEntityData, 'legal_entity_address.country'), Arr::get($response->Json(), 'legal_entity_address.country'));
    }

    public function testItUpdateLegalEntity()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $legalEntity = factory(LegalEntity::class)->create();
        $legalEntity->name = "update";
        $address = factory(CustomerAddress::class)->make();
        $europenBankAddress = factory(Bank::class)->make();
        $americanBankAddress = factory(Bank::class)->make();
        $legalEntity->address->fill($address->toArray());
        $legalEntity->europeanBank->load('address')->fill($europenBankAddress->toArray());
        $legalEntity->americanBank->load('address')->fill($americanBankAddress->toArray());
        $legalEntityData = $legalEntity->toArray();
        $legalEntityData['european_bank']['bank_address'] = $legalEntityData['european_bank']['address'];
        $legalEntityData['american_bank']['bank_address'] = $legalEntityData['american_bank']['address'];
        $legalEntityData['legal_entity_address'] = Arr::except($legalEntityData['address'], ['created_at', 'updated_at', 'id']);
        $legalEntityData['european_bank']['bank_address'] = Arr::except($legalEntityData['european_bank']['address'], ['created_at', 'updated_at', 'id']);
        $legalEntityData['american_bank']['bank_address'] = Arr::except($legalEntityData['american_bank']['address'], ['created_at', 'updated_at', 'id']);

        unset($legalEntityData['european_bank']['address']);
        unset($legalEntityData['american_bank']['address']);
        unset($legalEntityData['address']);
        /** EXECUTE */
        $response = $this->patch(route('api.v1.companies.legal_entities.update', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity->id,
        ]), $legalEntityData, array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));
        /** ASSERT */
        $response->isOk();
        $this->assertEquals(Arr::get($legalEntityData, 'name'), Arr::get($response->Json(), 'name'));
        $this->assertEquals(Arr::get($legalEntityData, 'vat_number'), Arr::get($response->Json(), 'vat_number'));
        $this->assertEquals(Arr::get($legalEntityData, 'european_bank.bank_address.swift'), Arr::get($response->Json(), 'european_bank.bank_address.swift'));
        $this->assertEquals(Arr::get($legalEntityData, 'european_bank.bank_address.bic'), Arr::get($response->Json(), 'european_bank.bank_address.bic'));
        $this->assertEquals(Arr::get($legalEntityData, 'european_bank.bank_address.country'), Arr::get($response->Json(), 'european_bank.bank_address.country'));
        $this->assertEquals(Arr::get($legalEntityData, 'american_bank.bank_address.swift'), Arr::get($response->Json(), 'american_bank.bank_address.swift'));
        $this->assertEquals(Arr::get($legalEntityData, 'american_bank.bank_address.bic'), Arr::get($response->Json(), 'american_bank.bank_address.bic'));
        $this->assertEquals(Arr::get($legalEntityData, 'american_bank.bank_address.country'), Arr::get($response->Json(), 'american_bank.bank_address.country'));
        $this->assertEquals(Arr::get($legalEntityData, 'legal_entity_address.country'), Arr::get($response->Json(), 'legal_entity_address.country'));
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
        $response->isEmpty();
        $this->assertSoftDeleted((new LegalEntity)->getTable(), [
            'id' => $legalEntity->id
        ]);
    }
}
