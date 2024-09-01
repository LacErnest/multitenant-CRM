<?php

namespace App\Repositories\Elastic;

use App\Models\Quote;

class ElasticQuoteRepository
{


    public function getRevenueFromOtherQuotes($quote): float
    {
        $numbersArray = [];
        while ($quote['this_is_quote'] > 1) {
            $quote['this_is_quote'] -= 1;
            array_push($numbersArray, $quote['this_is_quote']);
        }

        $query = [
          'size' => 0,
          'query' => [
              'bool' => [
                  'must' => [
                      [
                          'bool' => [
                              'must' => [
                                  'match' => ['order_id' => $quote['order_id']],
                              ]
                          ]
                      ],
                      [
                          'bool' => [
                              'must' => [
                                  ['terms' => ['this_is_quote' => $numbersArray]]
                              ]
                          ]
                      ],
                  ],
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
          ],
        ];

        $quotes = Quote::searchAllTenantsQuery('quotes', $query);

        return $quotes['aggregations']['monetary_value']['value']
          - $quotes['aggregations']['vat_value']['value'];
    }
}
