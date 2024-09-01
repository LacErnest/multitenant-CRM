<?php

use App\Enums\CurrencyCode;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_tenant_id')->nullable();
            $table->string('xero_access_token',1500)->nullable();
            $table->string('xero_refresh_token',255)->nullable();
            $table->string('name');
            $table->unsignedTinyInteger('currency_code')->default(CurrencyCode::EUR()->getIndex());
            $table->date('acquisition_date')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
