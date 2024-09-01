<?php

namespace Tests\Feature;

use App\Http\Resources\LegalEntity\CompanyLegalEntityResource;
use App\Models\Comment;
use App\Models\Company;
use App\Models\LegalEntity;
use Illuminate\Support\Arr;
use Tests\ApiTenantTestCase;

/**
 * @group company-legal-entity
 * @group company-legal-entity-api-controller-test
 */
class CompanyLegalEntityControllerApiTest extends ApiTenantTestCase
{
    public function testItLinkCompanyToLegalEntity()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $legalEntity = factory(LegalEntity::class)->create();
        /** EXECUTE */
        $response = $this->post(route('api.v1.companies.company_legal_entities.link', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity->id,
        ]), [], array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        /** ASSERT */
        $legalEntity = $this->company->legalEntities()->where('legal_entities.id', $legalEntity->id)->first();
        $resourceResponse = CompanyLegalEntityResource::make($legalEntity)->toResponse($this->app['request']);

        $response->isOk();
        $this->assertEquals($resourceResponse->getData(true), $response->json());
    }

    public function testItUnlinkCompanyToLegalEntity()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $legalEntity = factory(LegalEntity::class)->create();
        $legalEntity2 = factory(LegalEntity::class)->create();

        /** EXECUTE */
        $this->post(route('api.v1.companies.company_legal_entities.link', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity->id,
        ]), [], array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        $response = $this->delete(route('api.v1.companies.company_legal_entities.unlink', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity->id,
        ]), [], $this->authHeaders);

        $this->post(route('api.v1.companies.company_legal_entities.link', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity2->id,
        ]), [], array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        $response2 = $this->delete(route('api.v1.companies.company_legal_entities.unlink', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity2->id,
        ]), [], $this->authHeaders);

        /** ASSERT */
        $response->isClientError();
        $this->assertEquals('This legal entity is set as default, unset it first.', $response->json('message'));
        $response2->isOk();
        //dd($response2->json());
        $this->assertEquals([], $response2->json());
    }

    public function testItSuggestLegalEntity()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $legalEntities = factory(LegalEntity::class, 10)->create();
        $mySearchlegal = factory(LegalEntity::class)->create(['name' => 'Find me']);

        /** EXECUTE */
        $response = $this->get(route('api.v1.companies.company_legal_entities.suggest', [
            'company_id' => $this->tenant->id,
            'value' => 'Find me',
        ]), $this->authHeaders);

        /** ASSERT */
        $response->isOk();
        $this->assertEquals('Find me', Arr::get($response, 'suggestions')[0]['name']);
    }

    public function testItSetDefaultLegalEntity()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $legalEntity1 = factory(LegalEntity::class)->create();
        $legalEntity2 = factory(LegalEntity::class)->create();

        /** EXECUTE */
        $this->post(route('api.v1.companies.company_legal_entities.link', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity1->id,
        ]), [], array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));
        $this->post(route('api.v1.companies.company_legal_entities.link', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity2->id,
        ]), [], array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        $response = $this->patch(route('api.v1.companies.company_legal_entities.set.default', [
            'company_id' => $this->tenant->id,
            'legal_entity_id' => $legalEntity2->id,
        ]), [], array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        /** ASSERT */
        $response->isOk();
        $this->assertEquals('Legal entity set as default.', $response->json());
    }
}
