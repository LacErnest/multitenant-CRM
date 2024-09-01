<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterSalesCommissionsTableAddSecondSale extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->float('second_sale_commission')->default(5)->after('commission');
            $table->unsignedTinyInteger('commission_model')->default(0)->after('sales_person_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sales_commissions', function (Blueprint $table) {
            $table->dropColumn('second_sale_commission','commission_model');
        });
    }
}
