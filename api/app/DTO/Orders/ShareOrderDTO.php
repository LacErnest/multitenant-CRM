<?php

namespace App\DTO\Orders;

use App\Contracts\DTO\Orders\OrderModificationDTO;
use App\DTO\BaseDTO;

/**
 * Class ShareOrderDTO
 *
 * nullable on export of all collection
 * @property bool $master
 *
 * @method bool getMaster
 */
class ShareOrderDTO extends BaseDTO implements OrderModificationDTO
{
    public static function mapping(): array
    {
        return  [[
          'source' => 'master',
          'target' => 'master',
        ]];
    }
}
