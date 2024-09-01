<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id')->nullable();
            $table->uuid('company_id')->nullable();
            $table->unsignedTinyInteger('status')->default(0)->nullable();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->unsignedTinyInteger('industry')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('tax_number')->nullable();
            $table->unsignedTinyInteger('default_currency')->nullable();
            $table->string('website')->nullable();
            $table->string('phone_number')->nullable();
            $table->uuid('primary_contact_id')->nullable();
            $table->uuid('sales_person_id')->nullable();
            $table->uuid('billing_address_id')->nullable();
            $table->uuid('operational_address_id')->nullable();
            $table->integer('accounts_receivable')->nullable();
            $table->boolean('legacy_customer')->default(0);
            $table->timestamps();

            $table->foreign('sales_person_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('company_id')->references('id')->on('companies')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customers');
    }
}
