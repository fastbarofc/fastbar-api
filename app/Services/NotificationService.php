<?php

namespace App\Services;

use App\Enums\NotificationTypeEnum;
use App\Models\Notification;
use App\Models\NotificationType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;

class NotificationService
{
    public function listForUser(User $user, int $perPage = 15): LengthAwarePaginator
    {
        return Notification::query()
            ->with('type')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function listUnread(User $user, int $limit = 5): Collection
    {
        return Notification::query()
            ->with('type')
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function countUnread(User $user): int
    {
        return Notification::query()
            ->where('user_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(Notification $notification): Notification
    {
        if (is_null($notification->read_at)) {
            $notification->read_at = now();
            $notification->save();
        }

        return $notification;
    }

    public function create(
        NotificationTypeEnum|string $typeName,
        User $user,
        string $title,
        string $message,
        ?array $data = null,
        ?Model $sourceable = null,
    ): Notification {
        $type = $this->resolveType($typeName);

        return Notification::create([
            'notification_type_id' => $type->id,
            'user_id' => $user->id,
            'title' => $title,
            'message' => $message,
            'data' => $data ?? [],
            'sourceable_type' => $sourceable?->getMorphClass(),
            'sourceable_id' => $sourceable?->getKey(),
        ]);
    }

    protected function resolveType(NotificationTypeEnum|string $typeName): NotificationType
    {
        $value = $typeName instanceof NotificationTypeEnum ? $typeName->value : $typeName;

        return NotificationType::where('name', $value)->firstOrFail();
    }
}

