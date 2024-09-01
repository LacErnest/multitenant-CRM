<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuoteSalesPersonsTable extends Migration
{
    public function up()
    {
        Schema::create('quote_sales_persons', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('quote_id');
            $table->uuid('user_id');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('quote_sales_persons');
    }
}
