<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self discount()
 * @method static self charge()
 *
 * @method static bool isDiscount(int|string $value = null)
 * @method static bool isCharge(int|string $value = null)
 */

final class PriceModifierType extends Enum
{
    const MAP_INDEX = [

        'discount' => 0,
        'charge' => 1,
    ];

    const MAP_VALUE = [
        'discount' => 'Discount',
        'charge' => 'Charge',
    ];
}
