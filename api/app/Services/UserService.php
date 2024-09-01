<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Http\Requests\User\UpdateMailPreferencesRequest;
use App\Http\Requests\User\UserCreateRequest;
use App\Http\Requests\User\UserUpdateRequest;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Password;

class UserService
{
    protected UserRepository $user_repository;

    public function __construct(UserRepository $user_repository)
    {
        $this->user_repository = $user_repository;
    }

    public function create($company_id, UserCreateRequest $request)
    {
        return $this->user_repository->create($company_id, $request->allSafe());
    }

    public function update($companyId, $user_id, UserUpdateRequest $request)
    {
        return $this->user_repository->update($companyId, $user_id, $request->allSafe());
    }

    public function delete($array)
    {
        return $this->user_repository->delete($array);
    }

    public function suggest($company_id, Request $request, $value)
    {
        return $this->user_repository->suggest($company_id, $request, $value);
    }

    public function suggestSalesPersonThroughAllCompanies(string $value, ?string $companyId)
    {
        return $this->user_repository->suggestSalesPersonThroughAllCompanies($value, $companyId);
    }

    public function getMailPreferences()
    {
        return $this->user_repository->getMailPreferences();
    }

    public function updateMailPreferences(UpdateMailPreferencesRequest $request)
    {
        return $this->user_repository->updateMailPreferences($request->allSafe());
    }

    public function suggestProjectManager(string $companyId, ?string $value)
    {
        $searchQuery = [];
        $typeQuery = [
          ['bool' => [
              'should' => [
                  ['match' => ['role' => UserRole::admin()->getIndex()]],
                  ['match' => ['role' => UserRole::owner()->getIndex()]],
                  ['match' => ['role' => UserRole::pm()->getIndex()]],
                  ['match' => ['role' => UserRole::pm_restricted()->getIndex()]],
              ],
              'minimum_should_match' => 1
          ]
          ]
        ];


        if (!$value === null) {
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
                      [
                          [
                              'bool' => [
                                  'should' => [
                                      ['match' => ['company_id' => $companyId]],
                                      ['bool' => [
                                          'must_not' => [
                                              ['exists' => ['field' => 'company_id']]
                                          ]
                                      ]]
                                  ],
                                  'minimum_should_match' => 1,
                              ]
                          ],
                          [
                              'bool' => [
                                  'must_not' => [
                                      ['exists' => ['field' => 'disabled_at']]
                                  ]
                              ]
                          ],
                      ],
                      $searchQuery,
                      $typeQuery
                  )]
          ]
        ];

        $users = User::searchBySingleQuery($query, $limit);

        $result = $users['hits']['hits'];
        $result = array_map(function ($r) {
            $r = Arr::only($r['_source'], ['id', 'name']);
            return $r;
        }, $result);
        return response()->json($result);
    }

    public function resendLink(string $userId)
    {
        $user = User::findOrFail($userId);
        if ($user->password) {
            return 'User already set password.';
        } else {
            $token = Password::broker('new_users')->createToken($user);
            $user->sendPasswordSetNotification($token);
            return 'Link sent to user.';
        }
    }
}
