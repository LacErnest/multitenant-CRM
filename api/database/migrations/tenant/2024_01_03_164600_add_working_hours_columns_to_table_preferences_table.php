<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWorkingHoursColumnsToTablePreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $preferences = TablePreference::where('type', TablePreferenceType::employee_histories()->getIndex())
            ->get();
        $defaulColumns = config("table-config.employee_histories.default");
        foreach ($preferences as $preference) {
            $columns = array_merge(json_decode($preference->columns), ['working_hours']);
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
