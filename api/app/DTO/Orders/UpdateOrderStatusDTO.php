<?php

namespace App\DTO\Orders;

use App\Contracts\DTO\Orders\OrderModificationDTO;
use App\DTO\BaseDTO;
use Carbon\Carbon;

/**
 * Class UpdateOrderStatusDTO
 *
 * nullable on export of all collection
 * @property string $date
 * @property string $status
 *
 * @method string getDate
 * @method string getStatus
 */
class UpdateOrderStatusDTO extends BaseDTO implements OrderModificationDTO
{
    public static function mapping(): array
    {
        return  [[
          'source'   => 'date',
          'target'   => 'date',
          'resolve'  => fn (?string $date) => isset($date) ? (new Carbon($date))->toDate() : null,
          'nullable' => true,
        ],[
          'source' => 'status',
          'target' => 'status',
        ],[
          'source' => 'need_invoice',
          'target' => 'need_invoice',
          'nullable' => true,
        ],];
    }
}
