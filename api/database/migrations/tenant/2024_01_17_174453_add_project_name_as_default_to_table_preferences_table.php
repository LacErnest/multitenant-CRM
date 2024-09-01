<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProjectNameAsDefaultToTablePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $types = [
            TablePreferenceType::quotes()->getIndex(),
            TablePreferenceType::invoices()->getIndex(),
            TablePreferenceType::orders()->getIndex(),
            TablePreferenceType::purchase_orders()->getIndex(),
            TablePreferenceType::project_invoices()->getIndex(),
            TablePreferenceType::project_orders()->getIndex(),
            TablePreferenceType::project_purchase_orders()->getIndex(),
            TablePreferenceType::project_quotes()->getIndex()
        ];
        $preferences = TablePreference::whereIn('type', $types)->get();
        foreach ($preferences as $preference) {
            $type = strtolower(TablePreferenceType::make($preference->type)->getName());
            $defaulColumns = config("table-config.$type.default");
            $columns = array_merge(json_decode($preference->columns), ['project']);
            $preference->columns = sortListByIndex($defaulColumns, $columns);
            $preference->save();
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
