<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateQuotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('reference')->nullable();
            $table->string('reason_of_refusal')->nullable();
            $table->string('number')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('xero_id')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedTinyInteger('currency_code')->nullable();
            $table->date('date')->nullable();
            $table->date('email_sent_date')->nullable();
            $table->decimal('total_price', 15, 6)->default(0);
            $table->decimal('total_vat', 15, 6)->default(0);
            $table->decimal('total_price_usd', 15, 6)->default(0);
            $table->decimal('total_vat_usd', 15, 6)->default(0);
            $table->decimal('currency_rate_company', 13, 6)->nullable();
            $table->decimal('currency_rate_customer', 13, 6)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('quotes');
    }
}
