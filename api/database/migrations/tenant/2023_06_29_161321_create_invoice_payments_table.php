<?php

use App\Models\InvoicePayment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class CreateInvoicePaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('invoice_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('invoice_id')->nullable();
            $table->uuid('created_by')->nullable();
            $table->string('number')->nullable();
            $table->date('pay_date')->nullable();
            $table->unsignedTinyInteger('currency_code')->nullable();
            $table->decimal('pay_amount', 15, 6)->default(0);

            $table->timestamps();
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
        Schema::dropIfExists('invoice_payments');
    }
}

