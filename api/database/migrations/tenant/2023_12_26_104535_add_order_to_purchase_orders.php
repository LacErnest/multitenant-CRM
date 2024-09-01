<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Enums\TablePreferenceType;
use App\Models\TablePreference;

class AddOrderToPurchaseOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $preferences = TablePreference::where('type', TablePreferenceType::purchase_orders()->getIndex())->get();

            foreach ($preferences as $preference){
                $columnsArray = json_decode($preference->columns, true) ?? [];
                $numberPosition = array_search('number', $columnsArray);
                
                if ($numberPosition !== false) {
                    array_splice($columnsArray, $numberPosition + 1, 0, 'order_id');
                }
                $preference->columns = json_encode($columnsArray);
                $preference->save();
            }
    }

    public function down()
    {
        $preferences = TablePreference::where('type', TablePreferenceType::purchase_orders()->getIndex())->get();

        foreach ($preferences as $preference) {
            $columns = json_decode($preference->columns, true) ?? [];
            $columns = array_diff($columns, ['order_id']);
            $preference->columns = json_encode($columns);
            $preference->save();
        }
    }

}
