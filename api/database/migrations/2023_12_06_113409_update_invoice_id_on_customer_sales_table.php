<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;

class UpdateInvoiceIdOnCustomerSalesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        try {
            DB::beginTransaction();
            foreach (Company::all() as $company) {
                Tenancy::setTenant($company);
                foreach (Project::all() as $project) {
                    $invoices = Invoice::where('project_id', $project->id)->orderBy('created_at','ASC')->get();
                    $sales = DB::table('customer_sales')->where('project_id', $project->id)->orderBy('id','ASC')->get();
                    foreach ($sales as $index => $sale) {
                        if (isset($invoices[$index])) {
                            DB::table('customer_sales')
                                ->where('id', $sale->id)
                                ->update(['invoice_id' => $invoices[$index]->id]);
                        }
                    }
                }
            }
            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            throw $th;
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
