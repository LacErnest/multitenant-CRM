<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateTablePreferencesForProjectEntities extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        TablePreference::where('type', TablePreferenceType::project_purchase_orders()->getIndex())
            ->update(['columns' => '["number", "resource_id", "date", "delivery_date", "status", "total_price", "details"]']);

        TablePreference::where('type', TablePreferenceType::project_quotes()->getIndex())
            ->update(['columns' => '["number", "date", "expiry_date", "status", "total_price"]']);

        TablePreference::where('type', TablePreferenceType::project_invoices()->getIndex())
            ->update(['columns' => '["number", "order_id", "date", "due_date", "status", "pay_date", "total_price"]']);

        TablePreference::where('type', TablePreferenceType::project_resource_invoices()->getIndex())
            ->update(['columns' => '["number", "resource_id", "purchase_order_id", "date", "due_date", "status", "total_price"]']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        TablePreference::where('type', TablePreferenceType::project_purchase_orders()->getIndex())
            ->update(['columns' => '["number", "resource_id", "date", "delivery_date", "status", "details"]']);

        TablePreference::where('type', TablePreferenceType::project_quotes()->getIndex())
            ->update(['columns' => '["number", "date", "expiry_date", "status"]']);

        TablePreference::where('type', TablePreferenceType::project_invoices()->getIndex())
            ->update(['columns' => '["number", "order_id", "date", "due_date", "status", "pay_date"]']);

        TablePreference::where('type', TablePreferenceType::project_resource_invoices()->getIndex())
            ->update(['columns' => '["number", "resource_id", "purchase_order_id", "date", "due_date", "status"]']);
    }
}
