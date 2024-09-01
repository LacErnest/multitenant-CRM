<?php

use App\Services\TaxRateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class CreateTaxRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->float('tax_rate')->unsigned();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('xero_sales_tax_type')->nullable();
            $table->string('xero_purchase_tax_type')->nullable();
            $table->timestamps();
        });

        $this->fillRates();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tax_rates');
    }

    private function fillRates()
    {
        $taxRateService = App::make(TaxRateService::class);

        $rates = [
            [
            'tax_rate' => 23,
            'start_date' => '2012-01-01',
            'end_date' => '2020-08-31',
            ],
            [
            'tax_rate' => 21,
            'start_date' => '2020-09-01',
            'end_date' => '2021-02-28',
            ],
            [
            'tax_rate' => 23,
            'start_date' => '2021-03-01',
            ],
        ];

        foreach($rates as $rate) {
            $taxRateService->createRate($rate);
        }
    }
}
