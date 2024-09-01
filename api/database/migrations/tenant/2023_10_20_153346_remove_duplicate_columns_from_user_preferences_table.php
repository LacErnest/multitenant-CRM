<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveDuplicateColumnsFromUserPreferencesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tablePreferences = TablePreference::whereIn('type',[
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

        foreach($tablePreferences as $tablePreference){

            $columns = json_decode($tablePreference->columns);
            if(!empty($columns) && is_array($columns)){

                if(in_array('total_price',$columns)){
                    $columns = array_merge($columns, ['total_price_usd','customer_total_price']);
                }
    
                if($tablePreference->entity === TablePreferenceType::orders()->getIndex()){
                    if(in_array('gross_margin',$columns)){
                        $columns = array_merge($columns, ['gross_margin_usd','customer_gross_margin']);
                    }
                    if(in_array('costs',$columns)){
                        $columns = array_merge($columns, ['costs_usd','customer_costs']);
                    }
                    if(in_array('potential_gm',$columns)){
                        $columns = array_merge($columns, ['potential_gm_usd','customer_potential_gm']);
                    }
                    if(in_array('potential_costs',$columns)){
                        $columns = array_merge($columns, ['potential_costs_usd','customer_potential_costs']);
                    }
                }
    
                $tablePreference->columns = json_encode(array_unique($columns));
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
