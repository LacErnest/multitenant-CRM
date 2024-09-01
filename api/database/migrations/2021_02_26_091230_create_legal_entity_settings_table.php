<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLegalEntitySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legal_entity_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('quote_number')->nullable();
            $table->string('quote_number_format')->nullable();
            $table->string('order_number')->nullable();
            $table->string('order_number_format')->nullable();
            $table->string('invoice_number')->nullable();
            $table->string('invoice_number_format')->nullable();
            $table->string('purchase_order_number')->nullable();
            $table->string('purchase_order_number_format')->nullable();
            $table->string('resource_invoice_number')->nullable();
            $table->string('resource_invoice_number_format')->nullable();
            $table->timestamps();
        });

        Schema::table('legal_entities', function (Blueprint $table) {
            $table->foreign('legal_entity_setting_id')->references('id')->on('legal_entity_settings')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('legal_entities', function (Blueprint $table) {
            $table->dropForeign(['legal_entity_setting_id']);
        });

        Schema::dropIfExists('legal_entity_settings');
    }
}
