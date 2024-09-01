<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEarnOutStatusesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('earn_out_statuses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->bigInteger('quarter');
            $table->timestamp('approved')->nullable();
            $table->timestamp('confirmed')->nullable();
            $table->timestamp('received')->nullable();
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
        Schema::dropIfExists('earn_out_statuses');
    }
}
