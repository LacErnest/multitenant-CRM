<?php

namespace Tests\Unit;

use App\Enums\CurrencyCode;
use App\Enums\EmployeeType;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Customer;
use App\Models\Employee;
use App\Models\Item;
use App\Models\LegalEntity;
use App\Models\LegalEntitySetting;
use App\Models\Project;
use App\Models\Quote;
use App\Models\Service;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Tenancy\Facades\Tenancy;
use Tests\TenantTestCase;
use Tests\Traits\CreateProject;
use Tests\Traits\EntityTrait;
use Tests\Traits\GetCurrencyRate;

/**
 * Class ItEntityTotalPriceCalculationTest
 *
 * @group entity-total-price-calculation-test
 */
class ItEntityTotalPriceCalculationTest extends TenantTestCase
{
    use CreateProject, GetCurrencyRate, EntityTrait;
    public function testItCalculateQuoteTotalPriceInEuroWithoutPm()
    {
        /** PREPARE */
        $project = $this->createProject();
        $quote = $project->quotes->first();
        $totalAmountInEuro = $this->sumItemPrice($quote->items, 1);
        $amountToEUR = entityPrice(Quote::class, $quote->id, false, $this->getCurrencyRate(CurrencyCode::EUR()), false );
        /** ASSERT */
        $this->assertEquals($amountToEUR, $totalAmountInEuro);
    }

    public function testItCalculateQuoteTotalPriceInDollarWithoutPm()
    {
        /** PREPARE */
        $project = $this->createProject();
        $quote = $project->quotes->first();
        $totalAmountInEuro = $this->sumItemPrice($quote->items, $this->getCurrencyRate(CurrencyCode::USD()));
        $amountToUSD = entityPrice(Quote::class, $quote->id, false, $this->getCurrencyRate(CurrencyCode::USD()), false );
        /** ASSERT */
        $this->assertEquals(ceiling($amountToUSD,2), ceiling($totalAmountInEuro,2));
    }

    public function testItCalculateQuoteTotalPriceInRandomCurrencyWithoutPm()
    {
        /** PREPARE */
        $project = $this->createProject();
        $quote = $project->quotes->first();
        $currencyCode  = array_rand(CurrencyCode::getIndices());
        $totalAmountInEuro = $this->sumItemPrice($quote->items, $this->getCurrencyRate($currencyCode));
        $amountToAnyCurrency = entityPrice(Quote::class, $quote->id, false, $this->getCurrencyRate($currencyCode), false );
        /** ASSERT */
        $this->assertEquals(ceiling($amountToAnyCurrency,2), ceiling($totalAmountInEuro,2));
    }
}
