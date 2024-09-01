<?php

namespace App\Listeners;

use App\Models\Customer;
use App\Models\User;
use Elasticquent\ElasticquentClientTrait;
use Exception;
use Illuminate\Support\Str;
use Tenancy\Affects\Connections\Support\Traits\OnTenant;
use Tenancy\Facades\Tenancy;
use Tenancy\Hooks\Database\Events\ConfigureDatabaseMutation;
use Tenancy\Tenant\Events\Deleted;
use function Clue\StreamFilter\fun;

class ConfigureTenantDatabaseMutations
{
    use ElasticquentClientTrait;
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle(ConfigureDatabaseMutation $event)
    {
        if ($event->event->tenant) {
            if ($event->event instanceof Deleted) {
                $tenant = $event->event->tenant;
                Tenancy::setTenant($tenant);

                $query = [
                'query' => [
                'bool' => [
                    'must' => [
                        ['match' => ['company_id' => $tenant->id]]
                    ]
                ]
                ]
                ];
                User::deleteBySingleQuery($query);

                $customers = Customer::where('company_id', null)->get();
                $customers->each(function ($customer) {
                    try {
                        $customer->updateIndex();
                    } catch (Exception $e) {
                        logger($e->getMessage());
                    }
                });

                $indexes = indices();
                foreach ($indexes as $model) {
                    if (in_array(OnTenant::class, class_uses_recursive($model))) {
                        try {
                            $model::deleteIndex();
                        } catch (Exception $e) {
                            logger($e->getMessage());
                        }
                    }
                }
            }
        }
    }
}
