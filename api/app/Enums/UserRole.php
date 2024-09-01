<?php


namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self admin()
 * @method static self owner()
 * @method static self accountant()
 * @method static self sales()
 * @method static self pm()
 * @method static self hr()
 * @method static self owner_read()
 * @method static self pm_restricted()
 * @method static self super_admin()
 *
 * @method static bool isAdmin(int|string $value = null)
 * @method static bool isOwner(int|string $value = null)
 * @method static bool isAccountant(int|string $value = null)
 * @method static bool isSales(int|string $value = null)
 * @method static bool isPm(int|string $value = null)
 * @method static bool isHr(int|string $value = null)
 * @method static bool isOwner_read(int|string $value = null)
 * @method static bool isPm_restricted(int|string $value = null)
 * @method static bool isSuper_admin(int|string $value = null)
 */

final class UserRole extends Enum
{
    const MAP_INDEX = [
        'admin' => 0,
        'owner' => 1,
        'accountant' => 2,
        'sales' => 3,
        'pm' => 4,
        'hr' => 5,
        'owner_read' => 6,
        'pm_restricted' => 7,
        'super_admin' => 8,
    ];

    const MAP_VALUE = [
        'admin' => 'Administrator',
        'owner' => 'Owner',
        'accountant' => 'Accountant',
        'sales' => 'Sales Person',
        'pm' => 'Project Manager',
        'hr' => 'Human Resources',
        'owner_read' => 'Owner (read-only)',
        'pm_restricted' => 'Project Manager (restricted)',
        'super_admin' => 'Super Administrator',
    ];
}
