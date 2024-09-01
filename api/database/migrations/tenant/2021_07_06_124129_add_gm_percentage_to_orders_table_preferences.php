<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddGmPercentageToOrdersTablePreferences extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        TablePreference::where('type', TablePreferenceType::orders()->getIndex())
            ->update(['columns' => '["number", "project_manager_id", "quote_id", "customer_id", "contact_id", "date", "status", "total_price", "markup"]']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        TablePreference::where('type', TablePreferenceType::orders()->getIndex())
            ->update(['columns' => '["number", "project_manager_id", "quote_id", "customer_id", "contact_id", "date", "status", "total_price"]']);
    }
}
