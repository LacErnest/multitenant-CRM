<?php


namespace App\Repositories;

use App\Models\Contact;
use App\Models\Customer;
use App\Models\Resource;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ContactRepository
{
    protected Contact $contact;

    public function __construct(Contact $contact)
    {
        $this->contact = $contact;
    }

    public function get(Customer $customer, Contact $contact)
    {
        if (!$contact = $customer->contacts()->find($contact->id)) {
            throw new ModelNotFoundException();
        }
        return $contact;
    }

    public function create(array $attributes, Customer $customer)
    {
        $contact = $customer->contacts()->create($attributes);
        if ($customer->primary_contact_id == null || (!empty($attributes['primary_contact']) && $attributes['primary_contact'])) {
            $customer->primary_contact_id = $contact->id;
            $customer->save();
        }
        return $contact;
    }

    public function update($attributes, Customer $customer, Contact $contact)
    {
        if (!$contact = $customer->contacts()->find($contact->id)) {
            throw new ModelNotFoundException();
        }
        $contact->update($attributes);
        if (!empty($attributes['primary_contact']) && $attributes['primary_contact'] && $customer->primary_cotanct_id != $contact->id) {
            $customer->primary_contact_id = $contact->id;
            $customer->save();
        }
        return $contact;
    }

    public function delete($array, Customer $customer)
    {
        $array = $array->toArray();
        $count = 0;
        $message = '';
        foreach ($array as $id) {
            if ($id == $customer->primary_contact_id) {
                $message = 'Unable to delete primary contact, ';
            } else {
                $customer->contacts()->where('id', $id)->first()->delete();
                $count++;
            }
        }
        return $message = $message . ' deleted ' . $count . ' contact(s).';
    }


    public function suggest($company_id, Request $request, $value)
    {
        return $this->autoGet($company_id, $request, $value);
    }

    private function autoGet($company_id, Request $request, $value = null)
    {
        $searchQuery = [];

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
                                    'first_name' => [
                                        'value' => '*' . $value . '*'
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
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
                                    'first_name' => [
                                        'value' => $value . '*',
                                        'boost' => 5
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => $value . '*',
                                        'boost' => 5
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
                                    'first_name' => [
                                        'value' => $value,
                                        'boost' => 10
                                    ]
                                ]
                            ],
                            [
                                'wildcard' => [
                                    'last_name' => [
                                        'value' => $value,
                                        'boost' => 10
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

        $query = [
          'query' => [
              'bool' => [
                  'must' => array_merge(
                      $searchQuery
                  )]
          ]
        ];

        $users = Contact::searchBySingleQuery($query, $limit);

        $result = $users['hits']['hits'];

        $result = array_map(function ($r) {
            $r =  Arr::only($r['_source'], ['id', 'name']);
            return $r;
        }, $result);
        return response()->json($result);
    }
}
