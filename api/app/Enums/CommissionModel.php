<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self default()
 * @method static self lead_generation()
 * @method static self custom_modelA()
 * @method static self sales_support()
 * @method static self lead_generationB()
 *
 * @method static bool isDefault(int|string $value = null)
 * @method static bool isLead_generation(int|string $value = null)
 * @method static bool isCustom_modelA(int|string $value = null)
 * @method static bool isSales_support(int|string $value = null)
 * @method static bool isLead_generationB(int|string $value = null)
 */

final class CommissionModel extends Enum
{
    const MAP_INDEX = [
        'default' => 0,
        'lead_generation' => 1,
        'custom_modelA' => 2,
        'sales_support' => 3,
        'lead_generationB' => 4
    ];

    const MAP_VALUE = [
        'default' => 'Default',
        'lead_generation' => 'Lead Generation',
        'custom_modelA' => 'Custom Model A',
        'sales_support' => 'Sales Support',
        'lead_generationB' => 'Lead Generation B'
    ];
}
