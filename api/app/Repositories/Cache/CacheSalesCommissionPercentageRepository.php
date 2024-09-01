<?php


namespace App\Repositories\Cache;

use App\Models\SalesCommissionPercentage;
use Illuminate\Support\Facades\Cache;

class CacheSalesCommissionPercentageRepository extends CacheRepository
{

    public function all()
    {
        return SalesCommissionPercentage::all();
    }

    public function find($id)
    {
        //
    }

    public function findBySalesPersons(array $salesPpersons = [])
    {
        return SalesCommissionPercentage::all();
    }
}
