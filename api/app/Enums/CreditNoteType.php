<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self accpaycredit()
 * @method static self accreccredit()
 *
 * @method static bool isAccpaycredit(int|string $value = null)
 * @method static bool isAccreccredit(int|string $value = null)
 */

final class CreditNoteType extends Enum
{
    const MAP_INDEX = [
        'accpaycredit' => 0,
        'accreccredit' => 1,
    ];

    const MAP_VALUE = [
        'accpaycredit' => 'Accpaycredit',
        'accreccredit' => 'Accreccredit',
    ];
}
