<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('order_id')->nullable();
            $table->uuid('purchase_order_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->unsignedTinyInteger('type')->nullable();
            $table->date('date')->nullable();
            $table->date('due_date')->nullable();
            $table->unsignedTinyInteger('status')->default(0);
            $table->date('pay_date')->nullable();
            $table->string('number')->nullable()->unique();
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
            $table->foreign('order_id')->references('id')->on('orders')->onDelete('set null');
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('set null');
            //$table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('invoices');
    }
}
