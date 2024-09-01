<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMailPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('mail_preferences', function (Blueprint $table) {
            $table->uuid('id');
            $table->uuid('user_id');
            $table->boolean('customers')->default(0);
            $table->boolean('quotes')->default(0);
            $table->boolean('invoices')->default(0);
            $table->timestamps();

//            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mail_preferences');
    }
}
