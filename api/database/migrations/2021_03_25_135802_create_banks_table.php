<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBanksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('swift')->nullable();
            $table->string('bic')->nullable();
            $table->string('account_number')->nullable();
            $table->string('routing_number')->nullable();
            $table->uuid('address_id')->nullable();
            $table->timestamps();

            $table->foreign('address_id')->references('id')->on('customer_addresses')->onDelete('set null');
        });

        Schema::table('legal_entities', function (Blueprint $table) {
            $table->foreign('european_bank_id')->references('id')->on('banks')->onDelete('set null');
            $table->foreign('american_bank_id')->references('id')->on('banks')->onDelete('set null');
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
            $table->dropForeign(['european_bank_id']);
            $table->dropForeign(['american_bank_id']);
        });

        Schema::dropIfExists('banks');
    }
}
