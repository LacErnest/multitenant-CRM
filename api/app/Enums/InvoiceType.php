<?php

namespace App\Enums;

use Spatie\Enum\Enum;


/**
 * @method static self accpay()
 * @method static self accrec()
 *
 * @method static bool isAccpay(int|string $value = null)
 * @method static bool isAccrec(int|string $value = null)
 */

final class InvoiceType extends Enum
{
    const MAP_INDEX = [
        'accpay' => 0,
        'accrec' => 1,
    ];

    const MAP_VALUE = [
        'accpay' => 'Accpay',
        'accrec' => 'Accrec',
    ];
}
