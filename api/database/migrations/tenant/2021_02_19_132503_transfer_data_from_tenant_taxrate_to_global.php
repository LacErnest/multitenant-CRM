<?php

use App\Services\TaxRateService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Schema;

class TransferDataFromTenantTaxrateToGlobal extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $this->migrateTaxRateDataFromTenants();

        Schema::dropIfExists('tax_rates');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
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

        $this->rollbackTaxRateDataFromTenants();
    }

    private function migrateTaxRateDataFromTenants(): void
    {
        $taxRateService = App::make(TaxRateService::class);
        $taxRateService->migrateTaxRateDataTenants();
    }

    private function rollbackTaxRateDataFromTenants(): void
    {
        $taxRateService = App::make(TaxRateService::class);
        $taxRateService->rollbackTaxRateDataTenants();
    }
}
