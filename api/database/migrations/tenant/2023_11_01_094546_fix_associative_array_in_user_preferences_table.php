<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixAssociativeArrayInUserPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePreferences = TablePreference::whereIn('type', [
            TablePreferenceType::quotes()->getIndex(),
            TablePreferenceType::orders()->getIndex(),
            TablePreferenceType::invoices()->getIndex(),
            TablePreferenceType::resource_invoices()->getIndex(),
            TablePreferenceType::purchase_orders()->getIndex(),
            TablePreferenceType::project_invoices()->getIndex(),
            TablePreferenceType::project_orders()->getIndex(),
            TablePreferenceType::project_purchase_orders()->getIndex(),
            TablePreferenceType::project_quotes()->getIndex(),
            TablePreferenceType::project_resource_invoices()->getIndex()
        ])->get();

        foreach ($tablePreferences as $tablePreference) {
            $columns = json_decode($tablePreference->columns, true);
            if(!empty($columns) && is_array($columns)) {
                // Initialize an array to collect new column names
                $newColumns = $columns;

                // Check for specific column names and merge new columns as needed
                if (in_array('total_price', $columns)) {
                    $newColumns = array_merge($newColumns, ['total_price_usd', 'customer_total_price']);
                }

                if ($tablePreference->entity === TablePreferenceType::orders()->getIndex()) {
                    if (in_array('gross_margin', $columns)) {
                        $newColumns = array_merge($newColumns, ['gross_margin_usd', 'customer_gross_margin']);
                    }
                    if (in_array('costs', $columns)) {
                        $newColumns = array_merge($newColumns, ['costs_usd', 'customer_costs']);
                    }
                    if (in_array('potential_gm', $columns)) {
                        $newColumns = array_merge($newColumns, ['potential_gm_usd', 'customer_potential_gm']);
                    }
                    if (in_array('potential_costs', $columns)) {
                        $newColumns = array_merge($newColumns, ['potential_costs_usd', 'customer_potential_costs']);
                    }
                }

                // Remove duplicate values and re-index the array to ensure it's a sequential array
                $newColumns = array_values(array_unique($newColumns));

                // Encode the array back to JSON and save the record
                $tablePreference->columns = json_encode($newColumns);
                $tablePreference->save();
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
