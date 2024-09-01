<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('quote_number')->nullable();
            $table->string('quote_number_format')->nullable();
            $table->string('quote_template')->nullable();
            $table->string('order_number')->nullable();
            $table->string('order_number_format')->nullable();
            $table->string('order_template')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_number_format')->nullable();
            $table->string('invoice_template')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->string('purchase_order_number_format')->nullable();
            $table->string('purchase_order_template')->nullable();
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
        Schema::dropIfExists('settings');
    }
}
