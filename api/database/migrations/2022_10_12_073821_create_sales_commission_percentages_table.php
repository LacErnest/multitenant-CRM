<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesCommissionPercentagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_commission_percentages', function (Blueprint $table) {
            $table->id();
            $table->uuid('quote_id');
            $table->uuid('sales_person_id');//->foreign('sales_person_id')->references('id')->on('users')->onDelete('cascade');
            $table->float('commission_percentage');
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
        Schema::dropIfExists('sales_commission_percentages');
    }
}
