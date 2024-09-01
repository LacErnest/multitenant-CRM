<?php

use App\Models\Invoice;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddEligibleEarnoutToggleToInvoicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->boolean('eligible_for_earnout')->after('shadow')->default(true);
        });

        foreach (Invoice::all() as $invoice){
            $invoice->update([
                'eligible_for_earnout'=> !isIntraCompany($invoice->project->contact->customer->id ?? null)
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('invoices', function (Blueprint $table) {
            $table->dropColumn('eligible_for_earnout');
        });
    }
}
