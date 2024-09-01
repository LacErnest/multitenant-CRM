<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCreditNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('credit_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('xero_id')->nullable();
            $table->uuid('project_id')->nullable();
            $table->uuid('invoice_id')->nullable();
            $table->unsignedTinyInteger('type');
            $table->date('date');
            $table->unsignedTinyInteger('status');
            $table->string('number')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedTinyInteger('currency_code');
            $table->decimal('total_price', 15, 6)->default(0);
            $table->decimal('total_vat', 15, 6)->default(0);
            $table->decimal('total_price_usd', 15, 6)->default(0);
            $table->decimal('total_vat_usd', 15, 6)->default(0);
            $table->timestamps();

            $table->foreign('project_id')->references('id')->on('projects')->onDelete('set null');
            $table->foreign('invoice_id')->references('id')->on('invoices')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('credit_notes');
    }
}
