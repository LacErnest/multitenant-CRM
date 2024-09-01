<?php

namespace App\Enums;

use Spatie\Enum\Enum;

/**
 * @method static self due_in()
 * @method static self overdue_by()
 *
 * @method static bool isDue_in(int|string $value = null)
 * @method static bool isOverdue_by(int|string $value = null)
 */

final class NotificationReminderType extends Enum
{
    const MAP_INDEX = [
        'due_in' => 1,
        'overdue_by' => 2,
    ];

    const MAP_VALUE = [
        'due_in' => 'Due in',
        'overdue_by' => 'Overdue by',
    ];
}
