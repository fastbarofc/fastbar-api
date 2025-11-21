<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case EVENT_ALERT_45_DAYS = 'event_alert_45_days';

    public static function values(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }
}

