<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case EVENT_ALERT_45_DAYS = 'event_alert_45_days';
    case EVENT_MOVED_TO_PLANNING = 'event_moved_to_planning';
    case EVENT_PLANNING_BLOCKED = 'event_planning_blocked';

    public static function values(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }
}

