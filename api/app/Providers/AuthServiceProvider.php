<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Resource;
use App\Models\ResourceLogin;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tenancy\Facades\Tenancy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        // 'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Auth::viaRequest('resource-token', function ($request) {
            $resourceToken = $request->header('X-Auth');
            if ($resourceToken) {
                if (!Tenancy::isIdentified()) {
                    return null;
                }

                $resourceToken = json_decode(base64_decode($resourceToken), true);
                $records = ResourceLogin::where('resource_id', $resourceToken['resource_id'])->get();

                foreach ($records as $record) {
                    if (hash_equals($resourceToken['hash'], $record->token)) {
                        return $this->findResource($record->resource_id);
                    }
                }
            }
            return null;
        });
    }

    private function findResource($id)
    {
        $query = [
            'query' => [
                'bool' => [
                    'must' => [
                        'match' => ['id' => $id]
                    ]
                ]
            ]
        ];

        $result = Resource::searchAllTenantsQuery('resources', $query);
        if (!empty($result['hits']['hits'])) {
            $resource = App::make(Resource::class);
            $resource->id = $result['hits']['hits'][0]['_source']['id'];
            $resource->default_currency = $result['hits']['hits'][0]['_source']['default_currency'];
            $resource->daily_rate = $result['hits']['hits'][0]['_source']['daily_rate'];
            $resource->hourly_rate = $result['hits']['hits'][0]['_source']['hourly_rate'];
            $resource->can_be_borrowed = $result['hits']['hits'][0]['_source']['can_be_borrowed'];
            $resource->name = $result['hits']['hits'][0]['_source']['name'];
            $resource->first_name = $result['hits']['hits'][0]['_source']['first_name'];
            $resource->last_name = $result['hits']['hits'][0]['_source']['last_name'];
            $resource->type = $result['hits']['hits'][0]['_source']['type'];
            $resource->status = $result['hits']['hits'][0]['_source']['status'];
            $resource->email = $result['hits']['hits'][0]['_source']['email'];
            $resource->phone_number = $result['hits']['hits'][0]['_source']['phone_number'];
            $resource->company_id = $result['hits']['hits'][0]['_source']['company_id'];
            $resource->country = $result['hits']['hits'][0]['_source']['country'];
            $resource->tax_number = $result['hits']['hits'][0]['_source']['tax_number'];
            $resource->addressline_1 = $result['hits']['hits'][0]['_source']['addressline_1'];
            $resource->addressline_2 = $result['hits']['hits'][0]['_source']['addressline_2'];
            $resource->city = $result['hits']['hits'][0]['_source']['city'];
            $resource->region = $result['hits']['hits'][0]['_source']['region'];
            $resource->postal_code = $result['hits']['hits'][0]['_source']['postal_code'];

            return $resource;
        }
    }
}
