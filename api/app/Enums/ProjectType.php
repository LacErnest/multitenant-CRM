<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self intra_company()
 * @method static self extra_company()
 *
 * @method static bool isIntra_company(int|string $value = null)
 * @method static bool isExtra_company(int|string $value = null)
 */

final class ProjectType extends Enum
{

    const MAP_INDEX = [
        'intra_company' => true,
        'extra_company' => false,
    ];

    const MAP_VALUE = [
        'intra_company' => 'Intra company',
        'extra_company' => 'Extra company'
    ];
}
