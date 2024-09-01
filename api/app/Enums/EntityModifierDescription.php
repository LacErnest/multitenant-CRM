<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self project_management()
 * @method static self director_fee()
 * @method static self special_discount()
 * @method static self transaction_fee()
 *
 * @method static bool isProject_management(int|string $value = null)
 * @method static bool isDirector_fee(int|string $value = null)
 * @method static bool isSpecial_discount(int|string $value = null)
 * @method static bool isTransaction_fee(int|string $value = null)
 */

final class EntityModifierDescription extends Enum
{
    const MAP_INDEX = [

        'project_management' => 0,
        'director_fee' => 1,
        'special_discount' => 2,
        'transaction_fee' => 3,
    ];

    const MAP_VALUE = [
        'project_management' => 'Project Management',
        'director_fee' => 'Director Fee',
        'special_discount' => 'Special Discount',
        'transaction_fee' => 'Transaction Fee'
    ];
}
