<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePurchaseOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('resource_id')->nullable();
            $table->date('date')->nullable();
            $table->date('delivery_date')->nullable();
            $table->date('pay_date')->nullable();
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->string('reason_of_rejection')->nullable();
            $table->string('reason_of_penalty')->nullable();
            $table->unsignedTinyInteger('currency_code')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->decimal('total_vat', 15, 6)->default(0);
            $table->decimal('total_price', 15, 6)->default(0);
            $table->decimal('total_price_usd', 15, 6)->default(0);
            $table->decimal('total_vat_usd', 15, 6)->default(0);
            $table->decimal('currency_rate_company', 13, 6)->nullable();
            $table->decimal('currency_rate_customer', 13, 6)->nullable();
            $table->integer('penalty')->nullable();
            $table->tinyInteger('rating')->nullable();
            $table->string('reason',1000)->nullable();
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('resource_id')->references('id')->on('resources')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('purchase_orders');
    }
}
