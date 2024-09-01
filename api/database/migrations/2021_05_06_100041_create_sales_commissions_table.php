<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSalesCommissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sales_commissions', function (Blueprint $table) {
            $table->uuid('id');
            $table->float('commission');
            $table->uuid('sales_person_id');
            $table->timestamps();

            $table->foreign('sales_person_id')->references('id')->on('users')->onDelete('cascade');
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
            $table->dropForeign(['sales_person_id']);
        });

        Schema::dropIfExists('sales_commissions');
    }
}
