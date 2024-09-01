<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self calculated()
 * @method static self uncalculated()
 *
 * @method static bool isCalculated(int|string $value = null)
 * @method static bool isUncalculated(int|string $value = null)
 */

final class CommissionPercentageType extends Enum
{
    const MAP_INDEX = [
        'uncalculated' => 0,
        'calculated' => 1,
    ];

    const MAP_VALUE = [
        'uncalculated' => 'Not calculated',
        'calculated' => 'Calculated',
    ];
}
