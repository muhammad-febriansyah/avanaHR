<?php

namespace App\Approvals;

use App\Models\Notification;

/**
 * Emits approval notifications as pending email rows; the notifications:dispatch
 * command delivers them via SMTP.
 */
class ApprovalNotifier
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function toUser(int $tenantId, ?int $userId, string $type, array $payload): void
    {
        if ($userId === null) {
            return;
        }

        Notification::create([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'channel' => 'email',
            'type' => $type,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    /**
     * @param  array<int, int|null>  $userIds
     * @param  array<string, mixed>  $payload
     */
    public function toUsers(int $tenantId, array $userIds, string $type, array $payload): void
    {
        foreach (array_unique(array_filter($userIds)) as $userId) {
            $this->toUser($tenantId, (int) $userId, $type, $payload);
        }
    }
}
