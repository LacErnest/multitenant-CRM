<?php


namespace App\Repositories;

use App\Models\Address;
use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Tenancy\Facades\Tenancy;

class CustomerRepository
{
    protected Customer $customer;
    protected CustomerAddress $address;

    public function __construct(Customer $customer, CustomerAddress $address)
    {
        $this->customer = $customer;
        $this->address = $address;
    }

    public function get($customer_id)
    {
        if (!$customer = $this->customer->find($customer_id)) {
            throw new ModelNotFoundException();
        }
        return $customer;
    }

    public function create($company_id, array $attributes)
    {
        $attributes['company_id'] = $company_id;
        if ($customer = $this->customer->create($attributes)) {
            if (!empty($attributes['operational_addressline_1']) || !empty($attributes['operational_addressline_2']) || !empty($attributes['operational_city']) || !empty($attributes['operational_postal_code']) || !empty($attributes['operational_region']) || !empty($attributes['operational_country']) || !($attributes['operational_country'] === null)) {
                $operational_address = ['addressline_1' => $attributes['operational_addressline_1'] ?? null, 'addressline_2' => $attributes['operational_addressline_2'] ?? null, 'city' => $attributes['operational_city'] ?? null, 'postal_code' => $attributes['operational_postal_code'] ?? null, 'region' => $attributes['operational_region'] ?? null, 'country' => $attributes['operational_country'] ?? null];
                $address = $this->address->create($operational_address);
                $customer->operational_address_id = $address->id;
            }

            if ($attributes['is_same_address']) {
                $customer->billing_address_id = $customer->operational_address_id;
            } else {
                if (!empty($attributes['billing_addressline_1']) || !empty($attributes['billing_addressline_2']) || !empty($attributes['billing_city']) || !empty($attributes['billing_postal_code']) || !empty($attributes['billing_region']) || !empty($attributes['billing_country']) || !($attributes['billing_country'] === null)) {
                    $billing_address = ['addressline_1' => $attributes['billing_addressline_1'] ?? null, 'addressline_2' => $attributes['billing_addressline_2'] ?? null, 'city' => $attributes['billing_city'] ?? null, 'postal_code' => $attributes['billing_postal_code'] ?? null, 'region' => $attributes['billing_region'] ?? null, 'country' => $attributes['billing_country'] ?? null];
                    $address = $this->address->create($billing_address);
                    $customer->billing_address_id = $address->id;
                }
            }
            $customer->save();
        }
        return $customer;
    }

    public function update($customer_id, $attributes, $company_id)
    {
        if ($customer = $this->customer->find($customer_id)) {
            if (!empty($attributes['operational_addressline_1']) || !empty($attributes['operational_addressline_2']) || !empty($attributes['operational_city']) || !empty($attributes['operational_postal_code']) || !empty($attributes['operational_region']) || !empty($attributes['operational_country'])) {
                $operational_address = ['addressline_1' => $attributes['operational_addressline_1'] ?? null, 'addressline_2' => $attributes['operational_addressline_2'] ?? null, 'city' => $attributes['operational_city'] ?? null, 'postal_code' => $attributes['operational_postal_code'] ?? null, 'billing_region' => $attributes['operational_region'] ?? null, 'country' => $attributes['operational_country'] ?? null];
                if ($customer->operational_address) {
                    $customer->operational_address->update($operational_address);
                    $attributes['operational_address_id'] = $customer->operational_address_id;
                } else {
                    $address = $this->address->create($operational_address);
                    $attributes['operational_address_id'] = $address->id;
                }
            }

            if ($attributes['is_same_address']) {
                $attributes['billing_address_id'] = $attributes['operational_address_id'];
            } else {
                if (!empty($attributes['billing_addressline_1']) || !empty($attributes['billing_addressline_2']) || !empty($attributes['billing_city']) || !empty($attributes['billing_postal_code']) || !empty($attributes['billing_region']) || !empty($attributes['billing_country'])) {
                    $billing_address = ['addressline_1' => $attributes['billing_addressline_1'] ?? null, 'addressline_2' => $attributes['billing_addressline_2'] ?? null, 'city' => $attributes['billing_city'] ?? null, 'postal_code' => $attributes['billing_postal_code'] ?? null, 'billing_region' => $attributes['billing_region'] ?? null, 'country' => $attributes['billing_country'] ?? null];
                    if ($customer->billing_address && $customer->billing_address_id != $customer->operational_address_id) {
                        $customer->billing_address->update($billing_address);
                        $attributes['billing_address_id'] = $customer->billing_address_id;
                    } else {
                        $address = $this->address->create($billing_address);
                        $attributes['billing_address_id'] = $address->id;
                    }
                }
            }

            tap($customer)->update($attributes);
        }
        return $customer;
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->autoGet($company_id, $request, $value);
    }

    private function autoGet($company_id, Request $request, $value = null)
    {
        $searchQuery = [];
        $typeQuery = [];
        $type = !($request->input('type') === null) ? (int)$request->input('type') : null;

        if (!($value === null)) {
            $limit = 5;
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
                                    'name.keyword' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'name.keyword' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                        ]
                    ]
                ]
            );
        } else {
            $limit = null;
        }

        if (!($type === null)) {
            array_push($typeQuery, ['bool' => ['should' => [['match' => ['status' => $type]]]]]);
        }

        $query = [
            'query' => [
                'bool' => [
                    'must' => array_merge(
                        $searchQuery,
                        $typeQuery
                    )]
            ]
        ];


        $users = Customer::searchBySingleQuery($query, $limit);

        $result = $users['hits']['hits'];

        $result = array_map(function ($r) use ($company_id) {
            $m =  Arr::only($r['_source'], [
                'id',
                'name',
                'sales_person_id',
                'sales_person',
                'default_currency',
                'billing_country',
                'contacts',
                'primary_contact_id',
                'primary_contact',
                'non_vat_liable',
            ]);
            if ($r['_source']['company_id'] != $company_id) {
                unset($m['sales_person_id']);
                unset($m['sales_person']);
            }
            $m['legacy_customer'] = DB::table('legacy_customers')->where([['company_id', $company_id], ['customer_id', $m['id']]])->exists();
            return $m;
        }, $result);

        return response()->json($result);
    }
}
