<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeLengthCurrencyRates extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 21, 6)->change();
            $table->decimal('currency_rate_customer', 21, 6)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 21, 6)->change();
            $table->decimal('currency_rate_customer', 21, 6)->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 21, 6)->change();
            $table->decimal('currency_rate_customer', 21, 6)->change();
            $table->decimal('currency_rate_resource', 21, 6)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 21, 6)->change();
            $table->decimal('currency_rate_customer', 21, 6)->change();
        });

        Schema::table('employee_histories', function (Blueprint $table) {
            $table->decimal('currency_rate', 21, 6)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('quotes', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 13, 6)->change();
            $table->decimal('currency_rate_customer', 13, 6)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 13, 6)->change();
            $table->decimal('currency_rate_customer', 13, 6)->change();
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 13, 6)->change();
            $table->decimal('currency_rate_customer', 13, 6)->change();
            $table->decimal('currency_rate_resource', 13, 6)->change();
        });

        Schema::table('invoices', function (Blueprint $table) {
            $table->decimal('currency_rate_company', 13, 6)->change();
            $table->decimal('currency_rate_customer', 13, 6)->change();
        });

        Schema::table('employee_histories', function (Blueprint $table) {
            $table->decimal('currency_rate', 13, 6)->change();
        });
    }
}
