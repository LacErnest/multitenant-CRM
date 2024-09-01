<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('project_id')->nullable();
            $table->uuid('quote_id')->nullable();
            $table->date('date')->nullable();
            $table->date('deadline');
            $table->date('delivered_at')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedTinyInteger('currency_code')->nullable();
            $table->decimal('total_price', 15, 6)->default(0);
            $table->decimal('total_vat', 15, 6)->default(0);
            $table->decimal('total_price_usd', 15, 6)->default(0);
            $table->decimal('total_vat_usd', 15, 6)->default(0);
            $table->decimal('currency_rate_company', 13, 6)->nullable();
            $table->decimal('currency_rate_customer', 13, 6)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('quote_id')->references('id')->on('quotes')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}
