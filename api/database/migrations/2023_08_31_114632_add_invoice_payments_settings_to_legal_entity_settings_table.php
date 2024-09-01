<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddInvoicePaymentsSettingsToLegalEntitySettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('legal_entity_settings', function (Blueprint $table) {
            
            $table->string('invoice_payment_number')->nullable()
                ->after('resource_invoice_number')
                ->default(0);
            $table->string('invoice_payment_number_format')->nullable()
                ->after('invoice_payment_number')
                ->default('P-[YYYY][MM]XXXX');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('legal_entity_settings', function (Blueprint $table) {
            $table->dropColumn(['invoice_payment_number','invoice_payment_number_format']);
        });
    }
}
