<?php

use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateIntraCompanyFilterToTablePreferencesTable extends Migration
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
        ];
        $preferences = TablePreference::whereIn('type', $types)
            ->whereNotNull('filters')->get();
        foreach ($preferences as $preference) {
            $filters = json_decode($preference->filters, true);
            foreach ($filters as $index => $filter) {
                if ($filter['prop'] == 'intra_company') {
                    $filters[$index] = array_merge($filter, ['cast' => 'boolean']);
                    $preference->filters = json_encode($filters);
                    $preference->save();
                    break;
                }
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
