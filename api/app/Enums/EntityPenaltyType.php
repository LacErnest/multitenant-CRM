<?php

namespace App\Enums;

use Spatie\Enum\Enum;


/**
 * @method static self percentage()
 * @method static self fixed()
 *
 * @method static bool isPercentage(int|string $value = null)
 * @method static bool isFixed(int|string $value = null)
 */


final class EntityPenaltyType extends Enum
{
    const MAP_INDEX = [
        'Percentage' => 0,
        'Fixed' => 1,
    ];

    const MAP_VALUE = [
        'percentage' => 'Percentage',
        'fixed' => 'Fixed',
    ];
}
