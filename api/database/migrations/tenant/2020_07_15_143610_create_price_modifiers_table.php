<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePriceModifiersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('price_modifiers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('entity_type')->nullable();
            $table->unsignedTinyInteger('type')->nullable();
            $table->text('description')->nullable();
            $table->integer('quantity')->nullable();
            $table->unsignedTinyInteger('quantity_type')->nullable();
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
        Schema::dropIfExists('price_modifiers');
    }
}
