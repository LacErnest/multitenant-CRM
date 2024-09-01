<?php

use App\Models\Customer;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLegacyCustomersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('legacy_customers', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('company_id');
            $table->uuid('customer_id');
            $table->timestamps();
        });

        $this->addExistingLegacyCustomers();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('legacy_customers');
    }

    private function addExistingLegacyCustomers()
    {
        $customers = Customer::all();
        $customers->each(function ($customer) {
            if ($customer->legacy_customer) {
                $customer->legacyCompanies()->attach($customer->company_id);
            }
        });
    }
}
