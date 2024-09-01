<?php

namespace Tests\Feature;

use App\Enums\CurrencyCode;
use App\Enums\DownPaymentAmountType;
use App\Enums\QuoteStatus;
use App\Enums\UserRole;
use App\Models\Bank;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\CustomerAddress;
use App\Models\Item;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\Quote;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Tests\ApiTenantTestCase;
use Tests\Traits\GetCurrencyRate;

/**
 * @group legal-entity
 * @group legal-entity-api-controller-test
 */
class ProjectControllerApiTest extends ApiTenantTestCase
{
    use GetCurrencyRate;
    
    public function testItCreateProject()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $company = Company::find($this->tenant->id);
        $legalEntitySetting = factory(LegalEntitySetting::class)->create();
        $legalEntity = factory(LegalEntity::class)->create(['legal_entity_setting_id' => $legalEntitySetting->id]);
        $customer = factory(Customer::class)->create(['company_id' => $company->id]);
        $contact = factory(Contact::class)->create(['customer_id' => $customer->id]);
        $company->legalEntities()->attach($legalEntity);
        $salesPerson = factory(User::class)->create(['email' => 'sales@example.com', 'role' => UserRole::sales()->getIndex()]);
        $quote = [
            'name' => 'Test 01',
            'legal_entity_id' => $legalEntity->id,
            'sales_person_id' => [$salesPerson->id],
            'contact_id' => $contact->id,
            'expiry_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'manual_input' => false,
            'currency_code' => CurrencyCode::USD()->getIndex(),
            'master' => true,
            'down_payment_type' => DownPaymentAmountType::percentage()->getIndex(),
        ];

        /** EXECUTE */
        $response = $this->post(route('api.v1.companies.quotes.create', [
            'company_id' => $this->tenant->id,
        ]), $quote, array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        /** ASSERT */
        $response->assertCreated();
        $this->assertEquals(Arr::get($quote, 'name'), Arr::get($response->Json(), 'name'));
        $this->assertEquals(Arr::get($quote, 'legal_entity_id'), Arr::get($response->Json(), 'legal_entity_id'));
        $this->assertEquals(Arr::get($quote, 'contact_id'), Arr::get($response->Json(), 'contact_id'));
        $this->assertEquals(Arr::get($quote, 'manual_input'), Arr::get($response->Json(), 'manual_input'));
        //$this->assertEquals(Arr::get($quote, 'sales_person_id'), Arr::get($response->Json(), 'sales_person_id'));
        $this->assertEquals(Arr::get($quote, 'currency_code'), Arr::get($response->Json(), 'currency_code'));
        $this->assertEquals([], Arr::get($response->Json(), 'price_modifiers'));
        $this->assertEquals([], Arr::get($response->Json(), 'items'));
    }

    public function testItUpdateProject()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $company = Company::find($this->tenant->id);
        $legalEntitySetting = factory(LegalEntitySetting::class)->create();
        $legalEntity = factory(LegalEntity::class)->create(['legal_entity_setting_id' => $legalEntitySetting->id]);
        $customer = factory(Customer::class)->create(['company_id' => $company->id]);
        $contact = factory(Contact::class)->create(['customer_id' => $customer->id]);
        $company->legalEntities()->attach($legalEntity);
        $salesPerson = factory(User::class)->create(['email' => 'sales@example.com', 'role' => UserRole::sales()->getIndex()]);
        $quote = [
            'name' => 'Test 02',
            'legal_entity_id' => $legalEntity->id,
            'sales_person_id' => [$salesPerson->id],
            'contact_id' => $contact->id,
            'expiry_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'manual_input' => false,
            'currency_code' => CurrencyCode::USD()->getIndex(),
            'master' => true,
            'down_payment_type' => DownPaymentAmountType::percentage()->getIndex(),
        ];


        /** EXECUTE */
        $response = $this->post(route('api.v1.companies.quotes.create', [
            'company_id' => $this->tenant->id,
        ]), $quote, array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        $newQuote = factory(Quote::class)->make([
            'name' => Arr::get($response->Json(), 'name'),
            'project_id' => Arr::get($response->Json(), 'project_id'),
            'status' => Arr::get($response->Json(), 'status'),
            'date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'currency_code' => CurrencyCode::EUR()->getIndex()
        ]);
        $legalEntity2 = factory(LegalEntity::class)->create(['legal_entity_setting_id' => $legalEntitySetting->id]);
        $customer2 = factory(Customer::class)->create(['company_id' => $company->id]);
        $contact2 = factory(Contact::class)->create(['customer_id' => $customer2->id]);
        $salesPerson2 = factory(User::class)->create(['email' => 'sales2@example.com', 'role' => UserRole::sales()->getIndex()]);
        $newQuote->manual_input = Arr::get($response->Json(), 'manual_input');
        $newQuote->date = Arr::get($response->Json(), 'date');
        $newQuote->sales_person_id = $salesPerson2->id;
        $newQuote->contact_id = $contact2->id;
        $newQuote->legal_entity_id = $legalEntity2->id;
        /** EXECUTE */
        $response2 = $this->put(route('api.v1.companies.projects.quotes.update', [
            'company_id' => $this->tenant->id,
            'project_id' => Arr::get($response->Json(), 'project_id'),
            'quote_id' => Arr::get($response->Json(), 'id'),
        ]), $newQuote->toArray(), array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        /** ASSERT */
        $response->assertCreated();
        $this->assertEquals(Arr::get($quote, 'legal_entity_id'), Arr::get($response->Json(), 'legal_entity_id'));
        $this->assertEquals(Arr::get($quote, 'contact_id'), Arr::get($response->Json(), 'contact_id'));
        $this->assertEquals(Arr::get($quote, 'manual_input'), Arr::get($response->Json(), 'manual_input'));
        //$this->assertEquals(Arr::get($quote, 'sales_person_id'), Arr::get($response->Json(), 'sales_person_id'));
        $this->assertEquals(Arr::get($quote, 'currency_code'), Arr::get($response->Json(), 'currency_code'));
        $this->assertEquals([], Arr::get($response->Json(), 'price_modifiers'));
        $this->assertEquals([], Arr::get($response->Json(), 'items'));

        $response2->assertOk();
        $this->assertNotEquals($legalEntity2->id, Arr::get($response2->Json(), 'legal_entity_id'));
        $this->assertEquals($contact2->id, Arr::get($response2->Json(), 'contact_id'));
        $this->assertEquals(false, Arr::get($response2->Json(), 'manual_input'));
        //$this->assertEquals($salesPerson2->id, Arr::get($response2->Json(), 'sales_person_id'));
        $this->assertEquals(CurrencyCode::EUR()->getIndex(), Arr::get($response2->Json(), 'currency_code'));
    }

    public function testItProjectItem()
    {
        /** PREPARE */
        $this->actingAsUserWithJWT();
        $company = Company::find($this->tenant->id);
        $currencyRateCompany = 1 / $this->getCurrencyRate(CurrencyCode::make($company->currency_code)->__toString());
        $customerCurrency = CurrencyCode::USD()->__toString();
        $legalEntitySetting = factory(LegalEntitySetting::class)->create();
        $legalEntity = factory(LegalEntity::class)->create(['legal_entity_setting_id' => $legalEntitySetting->id]);
        $customer = factory(Customer::class)->create(['company_id' => $company->id]);
        $contact = factory(Contact::class)->create(['customer_id' => $customer->id]);
        $company->legalEntities()->attach($legalEntity);
        $salesPerson = factory(User::class)->create(['email' => 'sales@example.com', 'role' => UserRole::sales()->getIndex()]);
        $quote = [
            'name' => 'Test 03',
            'legal_entity_id' => $legalEntity->id,
            'sales_person_id' => [$salesPerson->id],
            'contact_id' => $contact->id,
            'expiry_date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'date' => \Carbon\Carbon::now()->format('Y-m-d'),
            'manual_input' => false,
            'currency_code' => CurrencyCode::USD()->getIndex(),
            'master' => true,
            'down_payment_type' => DownPaymentAmountType::percentage()->getIndex(),
        ];

        /** EXECUTE */
        $response = $this->post(route('api.v1.companies.quotes.create', [
            'company_id' => $this->tenant->id,
        ]), $quote, array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        $service = factory(Service::class)->create();
        $item = factory(Item::class)->make([
            'entity_id' => Arr::get($response->Json(), 'id'),
            'entity_type' => Quote::class,
        ]);

        /** EXECUTE */
        $response2 = $this->post(route('api.v1.companies.projects.quotes.items.create', [
            'company_id' => $this->tenant->id,
            'project_id' => Arr::get($response->Json(), 'project_id'),
            'quote_id' => Arr::get($response->Json(), 'id'),
        ]), $item->toArray(), array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        
        /** ASSERT */
        $response->assertCreated();
        $this->assertEquals(Arr::get($quote, 'legal_entity_id'), Arr::get($response->Json(), 'legal_entity_id'));
        $this->assertEquals(Arr::get($quote, 'contact_id'), Arr::get($response->Json(), 'contact_id'));
        $this->assertEquals(Arr::get($quote, 'manual_input'), Arr::get($response->Json(), 'manual_input'));
        //$this->assertEquals(Arr::get($quote, 'sales_person_id'), Arr::get($response->Json(), 'sales_person_id'));
        $this->assertEquals(Arr::get($quote, 'currency_code'), Arr::get($response->Json(), 'currency_code'));
        $this->assertEquals([], Arr::get($response->Json(), 'price_modifiers'));
        $this->assertEquals([], Arr::get($response->Json(), 'items'));

        /** ASSERT */
        $response2->assertCreated();
        $this->assertEquals($service->id, Arr::get($response2->Json(), 'service_id'));
        $this->assertEquals(Arr::get($response->Json(), 'id'), Arr::get($response2->Json(), 'entity_id'));
        $this->assertEquals(Quote::class, Arr::get($response2->Json(), 'entity_type'));
        $this->assertEquals($service->name, Arr::get($response2->Json(), 'service_name'));
        $this->assertEquals($item->description, Arr::get($response2->Json(), 'description'));
        $this->assertEquals($item->quantity, Arr::get($response2->Json(), 'quantity'));
        $this->assertEquals(false, Arr::get($response2->Json(), 'exclude_from_price_modifiers'));

        /** EXECUTE */
        $response3 = $this->get(route('api.v1.companies.projects.quotes.show', [
            'company_id' => $this->tenant->id,
            'project_id' => Arr::get($response->Json(), 'project_id'),
            'quote_id' => Arr::get($response->Json(), 'id'),
        ]), array_merge($this->authHeaders, [
            'Accept' => 'application/json'
        ]));

        /** ASSERT */
        $response3->assertOk();
        $this->assertEquals(Arr::get($response->Json(), 'id'), Arr::get($response3->Json(), 'id'));
        $this->assertEquals($service->id, Arr::get($response3->Json(), 'items.0.service_id'));
        $this->assertEquals($item->quantity, Arr::get($response3->Json(), 'items.0.quantity'));
        $this->assertEquals($item->quantity * $item->unit_price, Arr::get($response3->Json(), 'total_price'));
        $this->assertEquals(CurrencyCode::USD()->getIndex(), Arr::get($response3->Json(), 'currency_code'));
        $this->assertEquals($currencyRateCompany * $this->getCurrencyRate($customerCurrency), Arr::get($response3->Json(), 'currency_rate_customer'));
        if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
            $currencyRate = $currencyRateCompany * $this->getCurrencyRate($customerCurrency);
            $this->assertEquals($item->unit_price * $currencyRate, Arr::get($response3->Json(), 'items.0.unit_price'));
        } else {
            $this->assertEquals($item->unit_price, Arr::get($response3->Json(), 'items.0.unit_price'));
        }
    }
}
