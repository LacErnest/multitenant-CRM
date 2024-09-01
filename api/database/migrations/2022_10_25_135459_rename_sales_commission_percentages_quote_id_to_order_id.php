<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameSalesCommissionPercentagesQuoteIdToOrderId extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_commission_percentages', function (Blueprint $table) {
            $table->renameColumn('quote_id', 'order_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_commission_percentages', function (Blueprint $table) {
            $table->renameColumn('order_id', 'quote_id');
        });
    }
}
