<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddResourceInvoicesToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('resource_invoice_number')->nullable()
                ->after('purchase_order_template')
                ->default(0);
            $table->string('resource_invoice_number_format')->nullable()
                ->after('resource_invoice_number')
                ->default('RI-[YYYY][MM]XXXX');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('resource_invoice_number', 'resource_invoice_number_format');
        });
    }
}
