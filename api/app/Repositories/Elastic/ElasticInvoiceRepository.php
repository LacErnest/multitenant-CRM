<?php


namespace App\Repositories\Elastic;

use App\Enums\InvoiceStatus;
use App\Enums\InvoiceType;
use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\SalesCommissionPercentage;

class ElasticInvoiceRepository
{

    public function getCommissionInvoices(int $timeStart, int $timeEnd, array $filterBySalesPersonIds = [])
    {
        $limit = 10000;

        $cond = [
        [
            'bool' => [
                'must' => [
                    'range' => [
                        'pay_date' => [
                            'gte' => $timeStart,
                            'lte' => $timeEnd
                        ]
                    ],
                ]
            ]
        ],
        [
            'bool' => [
                'must' => [
                    ['term' => ['type' => InvoiceType::accrec()->getIndex()]]
                ]
            ]
        ],
        [
            'bool' => [
                'must' => [
                    ['term' => ['status' => InvoiceStatus::paid()->getIndex()]]
                ]
            ]
        ],
        [
            'bool' => [
                'must' => [
                    ['terms' => ['order_status' => [OrderStatus::delivered()->getIndex(), OrderStatus::invoiced()->getIndex()]]]
                ]
            ]
        ],
        [
            'bool' => [
                'must' => [
                    ['term' => ['shadow' => false]]
                ]
            ]
        ],
        ];

        if (count($filterBySalesPersonIds) > 0) {
            $invoicesIds = SalesCommissionPercentage::select('invoice_id')
            ->whereIn('sales_person_id', $filterBySalesPersonIds)
            ->pluck('invoice_id')->toArray();

            $cond[] = [
            'bool' => [
                'must' => [
                    ['terms' => ['id' => array_values($invoicesIds)]]
                ]
            ]
            ];
        }

        $query = [
          'sort' => [
              'pay_date' => 'asc',
              'created_at' => 'asc',
          ],
          'query' => [
              'bool' => [
                  'must' => $cond
              ]
          ],
        ];

        $invoices = Invoice::searchBySingleQuery($query, $limit);

        return $invoices['hits']['hits'];
    }


    public function getInvoicesUntilDate(?int $end, string $customerId, array $salespersonIds): array
    {

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'range' => [
                                      'pay_date' => [
                                          'gte' => strtotime('2015-01-01'),
                                          'lte' => $end ?? strtotime(now()->format('Y-m-d')),
                                      ]
                                  ],
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['type' => InvoiceType::accrec()->getIndex()]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['is_last_paid_invoice' => true]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['terms' => ['order_status' => [OrderStatus::delivered()->getIndex(), OrderStatus::invoiced()->getIndex()]]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['terms' => ['sales_person_id' => $salespersonIds]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['customer_id' => $customerId]]
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['match' => ['shadow' => false]]
                              ]
                          ]
                      ],
                  ],
              ]
          ],
          'aggs' => [
              'orders' => [
                  'terms' => [
                      'field' => 'order_id',
                      'size' => 10000
                  ],
              ],
          ],
        ];

        return Invoice::searchAllTenantsQuery('invoices', $query);
    }

    public function getCustomerRevenueUntilDate(?int $end, string $customerId, array $salespersonIds): float
    {
        $orderQuery = [];
        $revenue = 0;

        $orders = $this->getInvoicesUntilDate($end, $customerId, $salespersonIds);

        $orderIds = array_map(function ($order) {
            return $order['key'];
        }, $orders['aggregations']['orders']['buckets']);

        if ($orderIds) {
            array_push($orderQuery, ['bool' => ['should' => []]]);
            foreach ($orderIds as $order) {
                array_push($orderQuery[0]['bool']['should'], [
                  'match' => ['order_id' => $order]
                ]);
            }

            $query = [
            'size' => 0,
            'query' => [
                'bool' => [
                    'must' => [
                        $orderQuery,
                        [
                            'bool' => [
                                'must' => [
                                    'terms' => ['status' => [InvoiceStatus::paid()->getIndex(), InvoiceStatus::partial_paid()->getIndex()]]
                                ]
                            ]
                        ],
                        [
                            'bool' => [
                                'must' => [
                                    'match' => ['type' => InvoiceType::accrec()->getIndex()]
                                ]
                            ]
                        ],
                    ]
                ]
            ],
            'aggs' => [
                'monetary_value' => [
                    'sum' => [
                        'field' => 'total_price_usd'
                    ]
                ],
                'vat_value' => [
                    'sum' => [
                        'field' => 'total_vat_usd'
                    ]
                ],
            ]
            ];
            $invoices = Invoice::searchAllTenantsQuery('invoices', $query);

            $revenue = $invoices['aggregations']['monetary_value']['value']
            - $invoices['aggregations']['vat_value']['value'];
        }

        return round($revenue, 2);
    }
}
