<?php


namespace App\Repositories;


use App\Enums\CurrencyCode;
use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Tenancy\Facades\Tenancy;

class ServiceRepository
{
    protected Service $service;

    public function __construct(Service $service)
    {
        $this->service = $service;
    }

    public function create($attributes)
    {
        if (!array_key_exists('resource_id', $attributes) && UserRole::isAdmin(auth()->user()->role) && Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
            $rate = getCurrencyRates()['rates']['USD'];
            $attributes['price'] = $attributes['price'] * $rate;
        }

        return $this->service->create($attributes);
    }

    public function update($service_id, $attributes)
    {
        if (!array_key_exists('resource_id', $attributes) && UserRole::isAdmin(auth()->user()->role) && Company::find(getTenantWithConnection())->currency_code == CurrencyCode::USD()->getIndex()) {
            $rate = getCurrencyRates()['rates']['USD'];
            $attributes['price'] = $attributes['price'] * $rate;
        }
        $service = Service::findOrFail($service_id);

        return tap($service)->update($attributes);
    }

    public function delete($array)
    {
        $count = 0;
        foreach ($array as $item) {
            $service = Service::find($item);
            if ($service) {
                $service->delete();
                $count += 1;
            }
        }

        return $count;
    }

    public function suggest($value, $attributes): JsonResponse
    {
        $searchQuery= [];
        $resourceQuery = [];
        $resourceId = array_key_exists('resource', $attributes) ? $attributes['resource'] : null;
        $masterEntity = array_key_exists('company', $attributes) ? $attributes['company'] : null;
        $limit = 5;
        $company = Company::find(getTenantWithConnection());

        if ($masterEntity) {
            $shadowCompany = Company::findOrFail($attributes['company']);
            Tenancy::setTenant($shadowCompany);
        } else {
            $shadowCompany = $company;
        }

        if (!($value === null)) {
            $query_array = explode(' ', str_replace(['"', '+', '=', '-', '&&', '||', '>', '<', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '\\', '/'], '?', trim($value)));
            $query_array = array_filter($query_array);
            $query_string = implode(' ', $query_array);
            $value = strtolower($query_string);

            array_push(
                $searchQuery,
                [
                'bool' => [
                  'should' => [
                      [
                          'wildcard' => [
                              'name' => [
                                  'value' => '*' . $value . '*'
                              ]
                          ]
                      ],
                      [
                          'wildcard' => [
                              'name.keyword' => [
                                  'value' => '*' . $value . '*'
                              ]
                          ]
                      ],
                      [
                          'wildcard' => [
                              'name' => [
                                  'value' => $value . '*',
                                  'boost' => 5
                              ]
                          ]
                      ],
                      [
                          'wildcard' => [
                              'name.keyword' => [
                                  'value' => $value . '*'
                              ]
                          ]
                      ],
                      [
                          'wildcard' => [
                              'name' => [
                                  'value' => $value,
                                  'boost' => 10
                              ]
                          ]
                      ],
                      [
                          'wildcard' => [
                              'name.keyword' => [
                                  'value' => $value
                              ]
                          ]
                      ],
                  ]
                ]
                ]
            );
        }
        if (!($resourceId === null)) {
            array_push($resourceQuery, ['bool' => ['should' => [['match' => ['resource_id' => $resourceId]]]]]);
        } else {
            array_push($resourceQuery, ['bool' => ['must_not' => ['exists' => ['field' => 'resource_id']]]]);
        }

        $query = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      [
                          [
                              'bool' => [
                                  'must_not' => [
                                      'exists' => [
                                          'field' => 'deleted_at'
                                      ]
                                  ]
                              ]
                          ]
                      ],
                      $searchQuery,
                      $resourceQuery
                  )
              ]
          ]
        ];

        $services = $resourceId ? Service::searchAllTenantsQuery('services', $query, $limit) : Service::searchBySingleQuery($query, $limit);
        $result = $services['hits']['hits'];

        if (!empty($result)) {
            $rate = 1;
            if (UserRole::isAdmin(auth()->user()->role) && $company->currency_code == CurrencyCode::USD()->getIndex()) {
                $rate = 1/getCurrencyRates()['rates']['USD'];
            }

            $result = array_map(function ($r) use ($rate, $masterEntity, $company, $shadowCompany) {
                if ($masterEntity) {
                    if ($shadowCompany->currency_code != $company->currency_code) {
                        if ($shadowCompany->currency_code == CurrencyCode::EUR()->getIndex()) {
                            if (UserRole::isAdmin(auth()->user()->role)) {
                                $rate = 1;
                            } else {
                                $rate = getCurrencyRates()['rates']['USD'];
                            }
                        } else {
                            $rate = 1 / getCurrencyRates()['rates']['USD'];
                            ;
                        }
                    }
                }

                $r =  Arr::only($r['_source'], ['id', 'name', 'price', 'price_unit', 'resource_id']);
                $r['price'] = $r['resource_id'] ? $r['price'] : ceiling($r['price'] * $rate, 2);
                unset($r['resource_id']);
                return $r;
            }, $result);
        }

        Tenancy::setTenant($company);

        return response()->json($result ?? []);
    }
}
