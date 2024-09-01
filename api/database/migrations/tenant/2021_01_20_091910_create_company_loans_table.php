<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompanyLoansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('company_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('issued_at');
            $table->decimal('amount', 12)->default(0);
            $table->decimal('admin_amount', 12)->default(0);
            $table->date('paid_at')->nullable();
            $table->uuid('author_id')->nullable();
            $table->softDeletes();
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
        Schema::dropIfExists('company_loans');
    }
}
