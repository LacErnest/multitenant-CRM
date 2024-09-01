<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\CommissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tenancy\Facades\Tenancy;
class AddTypeToSalesCommissionPercentagesTable extends Migration
{

    /**
     * @var \App\Services\CommissionService
     */
    private $commissionService;

    public function __construct()
    {
        $this->commissionService = app(CommissionService::class);
    }

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sales_commission_percentages', function (Blueprint $table) {
            $table->unsignedTinyInteger('type')->default(0)
            ->after('commission_percentage');
        });

        foreach (Company::all() as $company) {
            Tenancy::setTenant($company);
            $invoice = Invoice::all();
            foreach($invoice as $invoice){
                $this->commissionService->createCommissionPercentagesFromInvoice($invoice);
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
        Schema::table('sales_commission_percentages', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
}
