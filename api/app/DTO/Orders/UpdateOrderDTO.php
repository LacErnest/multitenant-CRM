<?php

namespace App\DTO\Orders;

use App\Contracts\DTO\Orders\OrderModificationDTO;
use App\DTO\BaseDTO;
use Carbon\Carbon;
use DateTime;

/**
 * Class UpdateOrderDTO
 *
 * nullable on export of all collection
 * @property false|string $project_manager_id
 * @property DateTime     $date
 * @property string       $deadline
 *
 * nullable on export of all collection
 * @property null|string  $reference
 * @property string       $currency_code
 * @property boolean      $manual_input
 *
 * @method null|string getProjectManagerId
 * @method DateTime    getDate
 * @method string      getDeadline
 * @method null|string getReference
 * @method string      getCurrencyCode
 * @method boolean     getManualInput
 */
class UpdateOrderDTO extends BaseDTO implements OrderModificationDTO
{
    public static function mapping(): array
    {
        return [[
              'source'   => 'project_manager_id',
              'target'   => 'project_manager_id',
              'nullable' => true,
              'default'  => false,
          ], [
              'source' => 'date',
              'target' => 'date',
              'resolve' => fn (string $date) => (new Carbon($date))->toDate(),
          ], [
              'source' => 'deadline',
              'target' => 'deadline',
              'resolve' => fn (string $date) => (new Carbon($date))->toDate(),
          ], [
              'source'   => 'reference',
              'target'   => 'reference',
              'nullable' => true,
          ], [
              'source' => 'currency_code',
              'target' => 'currency_code',
          ], [
              'source' => 'manual_input',
              'target' => 'manual_input',
          ], [
              'source' => 'vat_status',
              'target' => 'vat_status',
              'nullable' => true,
        ], [
              'source' => 'vat_percentage',
              'target' => 'vat_percentage',
              'nullable' => true,
        ],
        ];
    }
}
