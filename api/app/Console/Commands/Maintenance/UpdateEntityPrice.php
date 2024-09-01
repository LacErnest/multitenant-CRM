<?php

namespace App\Console\Commands\Maintenance;

use App\Contracts\Repositories\GlobalTaxRateRepositoryInterface;
use App\Enums\CurrencyCode;
use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PurchaseOrder;
use App\Models\Quote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Tenancy\Environment;

class UpdateEntityPrice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'maintenance:update_entity_price';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'updates all quote, orders, ... with correct item price';

    protected GlobalTaxRateRepositoryInterface $globalTaxrateRepository;

    /**
     * Create a new command instance.
     *
     * @param GlobalTaxRateRepositoryInterface $globalTaxrateRepository
     */
    public function __construct(GlobalTaxRateRepositoryInterface $globalTaxrateRepository)
    {
        parent::__construct();
        $this->globalTaxrateRepository = $globalTaxrateRepository;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companies = Company::all();
        foreach ($companies as $company) {
            $environment = app(Environment::class);
            $environment->setTenant($company);

    //            $rates = getCurrencyRates();
    //            $rate = $rates['rates']['USD'];
    //            $rateUSD = 1 / $rate;
    //
    //            $legalEntityId = $company->defaultLegalEntity()->pivot->legal_entity_id;
    //            $this->globalTaxrateRepository->updateBy('belonged_to_company_id', $company->id, ['legal_entity_id' => $legalEntityId]);
    //
    //            $items = Quote::all();
    //            foreach ($items as $item) {
    //                $customerCurrency = CurrencyCode::make((int)$item->project->contact->customer->default_currency)->__toString();
    //                $taxRate = $this->globalTaxrateRepository->getTaxRateForPeriod($legalEntityId, $item->date);
    //                if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
    //                    $item->total_price_usd = entityPrice(Quote::class, $item->id);
    //                    $item->total_vat_usd = $item->total_vat == 0 ? $item->total_vat : $item->total_price_usd * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_company = $rateUSD;
    //                    $item->total_price_usd += $item->total_vat_usd;
    //                    $item->total_price = $item->total_price_usd * $rateUSD;
    //                    $item->total_vat = $item->total_vat == 0 ? $item->total_vat : $item->total_price * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_customer = $rateUSD * $rates['rates'][$customerCurrency];
    //                    $item->total_price += $item->total_vat;
    //                } else {
    //                    $item->total_price = entityPrice(Quote::class, $item->id);
    //                    $item->total_vat = $item->total_vat == 0 ? $item->total_vat : $item->total_price * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_company = $rate;
    //                    $item->total_price += $item->total_vat;
    //                    $item->total_price_usd = $item->total_price * $rate;
    //                    $item->total_vat_usd = $item->total_vat == 0 ? $item->total_vat : $item->total_price_usd * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_customer = $rates['rates'][$customerCurrency];
    //                    $item->total_price_usd += $item->total_vat_usd;
    //                }
    //                $item->currency_code = $item->project->contact->customer->default_currency;
    //                $item->legal_entity_id = $legalEntityId;
    //                $item->save();
    //            }
    //
    //            $items = Order::all();
    //            foreach ($items as $item) {
    //                $customerCurrency = CurrencyCode::make((int)$item->project->contact->customer->default_currency)->__toString();
    //                $taxRate = $this->globalTaxrateRepository->getTaxRateForPeriod($legalEntityId, $item->date);
    //                if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
    //                    $item->total_price_usd = entityPrice(Order::class, $item->id);
    //                    $item->total_vat_usd = $item->total_vat == 0 ? $item->total_vat : $item->total_price_usd * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_company = $rateUSD;
    //                    $item->total_price_usd += $item->total_vat_usd;
    //                    $item->total_price = $item->total_price_usd * $rateUSD;
    //                    $item->total_vat = $item->total_vat == 0 ? $item->total_vat : $item->total_price * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_customer = $rateUSD * $rates['rates'][$customerCurrency];
    //                    $item->total_price += $item->total_vat;
    //                } else {
    //                    $item->total_price = entityPrice(Order::class, $item->id);
    //                    $item->total_vat = $item->total_vat == 0 ? $item->total_vat : $item->total_price * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_company = $rate;
    //                    $item->total_price += $item->total_vat;
    //                    $item->total_price_usd = $item->total_price * $rate;
    //                    $item->total_vat_usd = $item->total_vat == 0 ? $item->total_vat : $item->total_price_usd * ($taxRate->tax_rate / 100);
    //                    $item->currency_rate_customer = $rates['rates'][$customerCurrency];
    //                    $item->total_price_usd += $item->total_vat_usd;
    //                }
    //                $item->currency_code = $item->project->contact->customer->default_currency;
    //                $item->legal_entity_id = $legalEntityId;
    //                $item->save();
    //            }

            $items = Invoice::all();
            foreach ($items as $item) {
        //                $customerCurrency = CurrencyCode::make((int)$item->project->contact->customer->default_currency)->__toString();
        //                $taxRate = $this->globalTaxrateRepository->getTaxRateForPeriod($legalEntityId, $item->date);
        //                if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
        //                    $item->total_price_usd = entityPrice(Invoice::class, $item->id);
        //                    $item->total_vat_usd = $item->total_vat == 0 ? $item->total_vat : $item->total_price_usd * ($taxRate->tax_rate / 100);
        //                    $item->currency_rate_company = $rateUSD;
        //                    $item->total_price_usd += $item->total_vat_usd;
        //                    $item->total_price = $item->total_price_usd * $rateUSD;
        //                    $item->total_vat = $item->total_vat == 0 ? $item->total_vat : $item->total_price * ($taxRate->tax_rate / 100);
        //                    $item->currency_rate_customer = $rateUSD * $rates['rates'][$customerCurrency];
        //                    $item->total_price += $item->total_vat;
      //                } else {
      //                    $item->total_price = entityPrice(Invoice::class, $item->id);
      //                    $item->total_vat = $item->total_vat == 0 ? $item->total_vat : $item->total_price * ($taxRate->tax_rate / 100);
      //                    $item->currency_rate_company = $rate;
      //                    $item->total_price += $item->total_vat;
      //                    $item->total_price_usd = $item->total_price * $rate;
      //                    $item->total_vat_usd = $item->total_vat == 0 ? $item->total_vat : $item->total_price_usd * ($taxRate->tax_rate / 100);
      //                    $item->currency_rate_customer = $rates['rates'][$customerCurrency];
      //                    $item->total_price_usd += $item->total_vat_usd;
      //                }
      //                $item->currency_code = $item->project->contact->customer->default_currency;
      //                $item->legal_entity_id = $legalEntityId;
                if ($item->pay_date) {
                    $item->updated_at = $item->pay_date;
                } else {
                    $item->updated_at = $item->date;
                }

                $item->save(['timestamps' => false]);
            }

  //            $items = PurchaseOrder::all();
  //            foreach ($items as $item) {
  //                $customerCurrency = CurrencyCode::make((int)$item->project->contact->customer->default_currency)->__toString();
  //                $taxRate = $this->globalTaxrateRepository->getTaxRateForPeriod($legalEntityId, $item->date);
  //                $resourceCurrency = CurrencyCode::make($item->resource->default_currency)->__toString();
  //                $item->currency_rate_resource = $rates['rates'][$resourceCurrency];
  //                $item->manual_price = entityPrice(PurchaseOrder::class, $item->id);
  //                $item->manual_vat = $item->total_vat == 0 ? $item->total_vat : $item->manual_price * ($taxRate->tax_rate / 100);
  //                $item->manual_price += $item->manual_vat;
  //                $item->currency_code = $item->resource->default_currency;
  //                $item->manual_price = entityPrice(PurchaseOrder::class, $item->id);
  //                $item->manual_vat = $item->total_vat == 0 ? $item->total_vat : $item->manual_price * ($taxRate->tax_rate / 100);
  //                $item->manual_price += $item->manual_vat;
  //                $item->currency_code = $item->resource->default_currency;
  //                if ($company->currency_code == CurrencyCode::USD()->getIndex()) {
  //                    $item->total_price_usd = $item->manual_price * (1/$item->currency_rate_resource) * $rate;
  //                    $item->total_vat_usd = $item->manual_vat * (1/$item->currency_rate_resource) * $rate;
  //                    $item->total_price = $item->total_price_usd * $rateUSD;
  //                    $item->total_vat =  $item->total_vat_usd * $rateUSD;
  //                    $item->currency_rate_company = $rateUSD;
  //                    $item->currency_rate_customer = $rateUSD * $rates['rates'][$customerCurrency];
  //                } else {
  //                    $item->total_price = $item->manual_price * (1/$item->currency_rate_resource);
  //                    $item->total_vat = $item->manual_vat * (1/$item->currency_rate_resource);
  //                    $item->total_price_usd = $item->total_price * $rate;
  //                    $item->total_vat_usd =  $item->total_vat * $rate;
  //                    $item->currency_rate_company = $rate;
  //                    $item->currency_rate_customer = $rates['rates'][$customerCurrency];
  //                }
  //                $item->legal_entity_id = $legalEntityId;
  //                $item->save();
  //            }
        }
        return 0;
    }
}
