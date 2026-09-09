<?php

namespace App\Enums;

enum AddressTypeEnum: string
{
    case MAIN = 'main';
    case REPRESENTATIVE = 'representative';

    public static function values(): array
    {
        return array_map(fn(self $type) => $type->value, self::cases());
    }
}
