<?php


namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self employee()
 * @method static self contractor()
 *
 * @method static bool isEmployee(int|string $value = null)
 * @method static bool isContractor(int|string $value = null)
 */

class EmployeeType extends Enum
{
    const MAP_INDEX = [
        'employee' => 0,
        'contractor' => 1,
    ];

    const MAP_VALUE = [
        'employee' => 'Employee',
        'contractor' => 'Contractor',
    ];
}
