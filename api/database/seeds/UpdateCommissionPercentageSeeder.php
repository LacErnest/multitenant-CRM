<?php

use App\Models\Company;
use App\Models\Invoice;
use App\Models\Order;
use App\Services\CommissionService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Tenancy\Facades\Tenancy;
use App\Traits\Models\UpdateSalesPersonRelationship;

class UpdateCommissionPercentageSeeder extends Seeder
{

    use UpdateSalesPersonRelationship;
    private CommissionService $commissionService;
    
    public function __construct() {
        $this->commissionService = app(CommissionService::class);
    }
    
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->updateSalesPersonRelationship();
    }
}
