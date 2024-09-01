<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerToProjectQuotesAndInvoices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        TablePreference::where('type', TablePreferenceType::project_quotes()->getIndex())
            ->update(['columns' => '["number", "customer_id", "date", "expiry_date","status", "total_price"]']);

        TablePreference::where('type', TablePreferenceType::project_invoices()->getIndex())
            ->update(['columns' => '["number", "order_id", "customer_id", "date", "due_date","status", "pay_date", "total_price"]']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        TablePreference::where('type', TablePreferenceType::project_quotes()->getIndex())
            ->update(['columns' => '["number", "date", "expiry_date","status", "total_price"]']);

        TablePreference::where('type', TablePreferenceType::project_invoices()->getIndex())
            ->update(['columns' => '["number", "order_id", "date", "due_date","status", "pay_date", "total_price"]']);
    }
}
