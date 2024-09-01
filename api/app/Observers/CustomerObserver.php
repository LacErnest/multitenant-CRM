<?php

namespace App\Observers;

use App\Jobs\XeroUpdate;
use App\Models\Company;
use App\Models\Customer;
use App\Services\Xero\Auth\AuthService as XeroAuthService;
use Exception;

class CustomerObserver
{
    public function created(Customer $customer)
    {
    }


    public function updated(Customer $customer)
    {
    }

    public function deleted(Customer $customer)
    {
    }

    public function saved(Customer $customer)
    {
    }
}
