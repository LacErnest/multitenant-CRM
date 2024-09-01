<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self freelancer()
 * @method static self supplier()
 *
 * @method static bool isFreelancer(int|string $value = null)
 * @method static bool isSupplier(int|string $value = null)
 */

final class ResourceType extends Enum
{
    const MAP_INDEX = [
        'freelancer' => 0,
        'supplier' => 1,
    ];

    const MAP_VALUE = [
        'freelancer' => 'Freelancer',
        'supplier' => 'Supplier',
    ];
}
