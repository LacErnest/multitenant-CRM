<?php

namespace App\Enums;

use Spatie\Enum\Enum;


/**
 * @method static self active()
 * @method static self potential()
 * @method static self archived()
 * @method static self inactive()
 *
 * @method static bool isActive(int|string $value = null)
 * @method static bool isPotential(int|string $value = null)
 * @method static bool isArchived(int|string $value = null)
 * @method static bool isInactive(int|string $value = null)
 */

final class CustomerStatus extends Enum
{
    const MAP_INDEX = [
        'active' => 0,
        'potential' => 1,
        'archived' => 2,
        'inactive' => 3,
    ];

    const MAP_VALUE = [
        'active' => 'Active',
        'potential' => 'Potential',
        'archived' => 'Archived',
        'inactive' => 'Inactive',
    ];
}
