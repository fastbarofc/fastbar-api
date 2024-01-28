<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class EventScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (empty($user) || $user->isAdmin()) {
            return;
        }

        $client = $user->client;

        if (empty($client)) {
            return;
        }

        $builder->whereHas('clients', function (Builder $query) use ($client) {
            $query->where('clients.id', $client->id);
        });
    }
}
