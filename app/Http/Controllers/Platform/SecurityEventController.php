<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\SecurityEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SecurityEventController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->only('search', 'type');

        $events = SecurityEvent::query()
            ->with(['user:id,name,email', 'tenant:id,name'])
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type))
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($q) use ($search) {
                $q->where('type', 'like', "%{$search}%")
                    ->orWhere('ip', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));
            }))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (SecurityEvent $event): array => [
                'id' => $event->id,
                'type' => $event->type,
                'user_name' => $event->user?->name,
                'user_email' => $event->user?->email,
                'tenant_name' => $event->tenant?->name,
                'ip' => $event->ip,
                'user_agent' => $event->user_agent ? Str::limit($event->user_agent, 60) : null,
                'meta' => $this->metaPairs($event),
                'created_at' => $event->created_at?->format('Y-m-d H:i:s'),
            ]);

        return Inertia::render('platform/security-events/index', [
            'breadcrumbs' => [
                ['title' => 'Dashboard', 'href' => route('dashboard')],
                ['title' => 'Security Events', 'href' => route('platform.security-events.index')],
            ],
            'events' => $events,
            'filters' => (object) $filters,
            'types' => $this->typeOptions(),
        ]);
    }

    /**
     * @return list<array{key: string, value: string}>
     */
    private function metaPairs(SecurityEvent $event): array
    {
        return collect($event->meta ?? [])
            ->map(fn (mixed $value, string $key): array => [
                'key' => $key,
                'value' => is_scalar($value) ? (string) $value : Str::limit((string) json_encode($value), 80),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    private function typeOptions(): array
    {
        return [
            ['value' => 'login_success', 'label' => 'Login Berhasil'],
            ['value' => 'login_failed', 'label' => 'Login Gagal'],
            ['value' => 'locked_out', 'label' => 'Akun Terkunci'],
            ['value' => 'password_changed', 'label' => 'Sandi Diubah'],
            ['value' => 'two_factor_enabled', 'label' => '2FA Aktif'],
            ['value' => 'suspicious_login', 'label' => 'Login Mencurigakan'],
        ];
    }
}
