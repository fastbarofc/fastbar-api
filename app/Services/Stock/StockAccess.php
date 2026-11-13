<?php

namespace App\Services\Stock;

use App\Models\User;

class StockAccess
{
    public const VIEW = 'estoque-visualizar';
    public const PURCHASES = 'estoque-compras';
    public const EVENTS = 'estoque-eventos';
    public const LIMITS = 'estoque-limites';

    public const ALL = [self::VIEW, self::PURCHASES, self::EVENTS, self::LIMITS];

    public static function can(?User $user, string ...$slugs): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->isAdmin()) {
            return true;
        }

        $granted = $user->getPermissionsViaRoles()->pluck('slug');

        return collect($slugs)->contains(fn ($slug) => $granted->contains($slug));
    }

    public static function authorize(?User $user, string ...$slugs): void
    {
        abort_unless(self::can($user, ...$slugs), 403, 'Você não tem permissão para esta ação do estoque.');
    }
}
