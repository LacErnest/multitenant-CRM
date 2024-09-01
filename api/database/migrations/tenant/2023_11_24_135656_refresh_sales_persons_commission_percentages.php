<?php

use App\Enums\QuoteStatus;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\Quote;
use App\Services\CommissionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RefreshSalesPersonsCommissionPercentages extends Migration
{

    /**
     * @var \App\Services\CommissionService
     */
    private $commissionService;

    public function __construct(){
        $this->commissionService = app(CommissionService::class);
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach(Invoice::all() as $invoice){
            $this->commissionService->createCommissionPercentagesFromInvoice($invoice);
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
