<?php

namespace App\Policies;

use App\Models\Stock;
use App\Models\User;

class StockPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('configuracoes-estoques-visualizar');
    }

    public function view(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('configuracoes-estoques-visualizar');
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('configuracoes-estoques-criar');
    }

    public function update(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('configuracoes-estoques-editar');
    }

    public function delete(User $user, Stock $stock): bool
    {
        return $user->hasPermissionTo('configuracoes-estoques-excluir');
    }

    public function restore(User $user, Stock $stock): bool
    {
        return false;
    }

    public function forceDelete(User $user, Stock $stock): bool
    {
        return false;
    }
}
