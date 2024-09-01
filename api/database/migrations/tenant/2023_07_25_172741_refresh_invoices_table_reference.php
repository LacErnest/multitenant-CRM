<?php

use App\Enums\InvoiceStatus;
use App\Enums\TablePreferenceType;
use App\Models\TablePreference;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefreshInvoicesTableReference extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $preference = TablePreference::where('type', TablePreferenceType::invoices()->getIndex())
            ->where('key', 'analytics')
            ->first();
        if ($preference && $preference->filters) {
            $filters = json_decode($preference->filters, true);
            foreach ($filters as $index => $filter) {
                if (!empty($filter['value']) && $filter['type'] === 'enum' && $filter['prop'] === 'status') {
                    $filters[$index]['value'] = array_merge($filters[$index]['value'], [InvoiceStatus::partial_paid()->getIndex()]);
                }
            }
            $preference->filters = json_encode($filters);
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
