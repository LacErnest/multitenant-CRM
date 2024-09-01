<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGlobalTaxRatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('global_tax_rates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('legal_entity_id')->nullable();
            $table->float('tax_rate')->unsigned();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('xero_sales_tax_type')->nullable();
            $table->string('xero_purchase_tax_type')->nullable();
            $table->uuid('belonged_to_company_id')->nullable();
            $table->timestamps();

            $table->foreign('legal_entity_id')->references('id')->on('legal_entities')->onDelete('cascade');
            $table->foreign('belonged_to_company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('global_tax_rates');
    }
}
