<?php


namespace App\Repositories;

use App\Contracts\Repositories\CompanyRepositoryInterface;
use App\Enums\CommissionModel;
use App\Enums\UserRole;
use App\Models\SalesCommission;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\UnauthorizedException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class UserRepository
{
    protected User $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    public function create($company_id, $attributes)
    {
        if ($attributes['role'] && !UserRole::isAdmin($attributes['role'])) {
            $attributes['company_id'] = $company_id;
        }

        $user = User::where('email', $attributes['email'])->orderBy('created_at', 'ASC')->first();
        $userWithSameCompanyExists = User::where([['email', $attributes['email']], ['company_id', $company_id]])->orderBy('created_at', 'ASC')->exists();

        if ($userWithSameCompanyExists) {
            throw new UnprocessableEntityHttpException('You cannot create a user with an email that\'s already used in the specified company');
        }

        $newUser = $this->user->create($attributes);

        if (Arr::exists($attributes, 'role') && UserRole::isSales($attributes['role'])) {
            $secondSale = 0;
            $salesConditionsChanged = false;
            if ($user) {
                $salesCommissionRecord = $user->salesCommissions->sortByDesc('created_at')->first();
                if ($salesCommissionRecord) {
                    if ($attributes['second_sale_commission'] === null) {
                        $attributes['second_sale_commission'] = 0;
                    }

                    if ($salesCommissionRecord->commission != $attributes['commission_percentage'] ||
                      $salesCommissionRecord->second_sale_commission != $attributes['second_sale_commission'] ||
                      $salesCommissionRecord->commission_model != $attributes['commission_model']) {
                        $salesConditionsChanged = true;
                    }
                }
            } else {
                $salesConditionsChanged = true;
            }

            if ($salesConditionsChanged) {
                if (Arr::exists($attributes, 'commission_model')) {
                    if (CommissionModel::isLead_generation($attributes['commission_model'])) {
                        $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_LEAD_COMMISSION;
                        $secondSale = $attributes['second_sale_commission'] ?? SalesCommission::DEFAULT_SECOND_SALE;
                    } elseif (CommissionModel::isLead_generationB($attributes['commission_model'])) {
                        $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_LEAD_COMMISSION_B;
                    } elseif (CommissionModel::isSales_support($attributes['commission_model'])) {
                        $this->setSalesSupportPercentage($company_id, $attributes['commission_percentage']);
                        $commission = $attributes['commission_percentage'];
                    } else {
                        $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_COMMISSION;
                    }
                    $commissionModel = $attributes['commission_model'];
                } else {
                    $commissionModel = CommissionModel::default()->getIndex();
                    $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_COMMISSION;
                }

                SalesCommission::create([
                'sales_person_id' => $user ? $user->id : $newUser->id,
                'commission' => $commission,
                'second_sale_commission' => $secondSale,
                'commission_model' => $commissionModel
                ]);
            }
        }

        if ($user) {
            $newUser->disablePasswordHashing();
            $newUser->primary_account = false;
            $newUser->password = $user->password;
            $newUser->google2fa_secret = $user->google2fa_secret ? Crypt::encryptString(Crypt::decryptString($user->google2fa_secret)) : null;
            $newUser->google2fa = $user->google2fa;
            $newUser->remember_token = $user->remember_token;

            $newUser->save();
        } else {
            $token = Password::broker('new_users')->createToken($newUser);
            $newUser->sendPasswordSetNotification($token);
        }

        return $newUser;
    }

    public function update($companyId, $user_id, $attributes)
    {
        $user = User::findOrFail($user_id);
        if (UserRole::isAdmin($user->role) && UserRole::isOwner(auth()->user()->role)) {
            throw new UnauthorizedException();
        }

        $linkedUsers = User::where('email', $user->email)->orderBy('created_at', 'ASC')->get();

        foreach ($linkedUsers as $linkedUser) {
            $linkedUser->email = $attributes['email'];
            $linkedUser->save();
        }

        if (UserRole::isSales($attributes['role'])) {
            $primaryUser = $linkedUsers->where('primary_account', true)->first() ?? $user;
            $secondSale = 0;
            $salesConditionsChanged = false;
            if (Arr::exists($attributes, 'commission_model') && !($attributes['commission_model'] === null)) {
                $salesCommissionRecord = $primaryUser->salesCommissions->sortByDesc('created_at')->first();
                info($salesCommissionRecord);
                if ($salesCommissionRecord) {
                    if ($attributes['second_sale_commission'] === null) {
                        $attributes['second_sale_commission'] = 0;
                    }
                    if (
                    $salesCommissionRecord->commission != $attributes['commission_percentage'] ||
                    $salesCommissionRecord->second_sale_commission != $attributes['second_sale_commission'] ||
                    $salesCommissionRecord->commission_model != $attributes['commission_model']
                    ) {
                        $salesConditionsChanged = true;
                    }
                } else {
                    $salesConditionsChanged = true;
                }

                if ($salesConditionsChanged) {
                    if (CommissionModel::isLead_generation($attributes['commission_model'])) {
                        $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_LEAD_COMMISSION;
                        $secondSale = $attributes['second_sale_commission'] ?? SalesCommission::DEFAULT_SECOND_SALE;
                    } elseif (CommissionModel::isSales_support($attributes['commission_model'])) {
                        $this->setSalesSupportPercentage($companyId, $attributes['commission_percentage'], $user_id);
                        $commission = $attributes['commission_percentage'];
                    } elseif (CommissionModel::isLead_generationB($attributes['commission_model'])) {
                        $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_LEAD_COMMISSION_B;
                    } else {
                        $commission = $attributes['commission_percentage'] ?? SalesCommission::DEFAULT_COMMISSION;
                    }
                      $commissionModel = $attributes['commission_model'];
                      SalesCommission::create([
                      'sales_person_id' => $primaryUser->id,
                      'commission' => $commission,
                      'second_sale_commission' => $secondSale,
                      'commission_model' => $commissionModel
                      ]);
                }
            }
        }

        $user = tap($user)->update($attributes);
        return $user;
    }

    public function delete($array)
    {
        $count = 0;
        foreach ($array as $item) {
            $user = User::find($item);
            if ($user) {
                if (UserRole::isAdmin($user->role) && UserRole::isOwner(auth()->user()->role)) {
                    continue;
                }
                $user->disabled_at = now();
                $user->save();
                $count += 1;
            }
        }
        return $count;
    }

    public function suggest($company_id, Request $request, $value)
    {
        $searchQuery = [];
        $typeQuery = [
          ['bool' => [
              'should' => [
                  ['match' => ['role' => UserRole::admin()->getIndex()]],
                  ['match' => ['role' => UserRole::owner()->getIndex()]],
                  ['match' => ['role' => UserRole::sales()->getIndex()]],
              ],
              'minimum_should_match' => 1
              ]
          ]
        ];


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
                      [
                          [
                              'bool' => [
                                  'should' => [
                                      ['match' => ['company_id' => $company_id]],
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

    public function suggestSalesPersonThroughAllCompanies(string $value, ?string $companyId): \Illuminate\Http\JsonResponse
    {
        $searchQuery = [];
        $companyQuery = [];
        $typeQuery = [
          ['bool' => [
              'must' => [
                  'match' => ['role' => UserRole::sales()->getIndex()]
                  ]
              ]
          ]
        ];

        if (!($companyId === null)) {
            $companyQuery = [
              ['bool' => [
                  'must' => [
                      'match' => ['company_id' => $companyId]
                      ]
                  ]
              ]
            ];
        }


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
                      $searchQuery,
                      $typeQuery,
                      $companyQuery
                  )]
          ]
        ];

        $users = User::searchBySingleQuery($query, $limit);

        $storedEmails = [];
        $result = $users['hits']['hits'];

        $result = array_map(function ($r) use (&$storedEmails) {
            $email = $r['_source']['email'];
            $r = Arr::only($r['_source'], ['id', 'name']);

            if (!in_array($email, $storedEmails)) {
                $storedEmails[] = $email;
                return $r;
            } else {
                return null;
            }
        }, $result);

        $result = array_values(array_filter($result));

        return response()->json($result);
    }

    public function getMailPreferences()
    {
        $user = auth()->user();
        return $user->mail_preference()->firstOrCreate(['user_id' => $user->id]);
    }

    public function updateMailPreferences($attributes)
    {
        $user = auth()->user();
        return $user->mail_preference()->updateOrCreate(['user_id' => $user->id], $attributes);
    }

    private function setSalesSupportPercentage(string $companyId, $percentage, $userId = null): void
    {
        $companyRepository = App::make(CompanyRepositoryInterface::class);
        $company = $companyRepository->firstById($companyId);
        if ($company->sales_support_commission != $percentage) {
            $companyRepository->update($companyId, ['sales_support_commission' => $percentage]);
            $salesIds = User::where([['company_id', $companyId], ['role', UserRole::sales()->getIndex()], ['id', '!=', $userId]])
            ->pluck('id')->toArray();
            $salesCommissions = SalesCommission::whereIn('sales_person_id', $salesIds)
            ->where('commission_model', CommissionModel::sales_support()->getIndex())
            ->get();

            foreach ($salesCommissions as $salesCommission) {
                SalesCommission::create([
                'commission' => $percentage,
                'second_sale_commission' => $salesCommission->second_sale_commission,
                'sales_person_id' => $salesCommission->sales_person_id,
                'commission_model' => $salesCommission->commission_model,
                ]);
            }
        }
    }
}
