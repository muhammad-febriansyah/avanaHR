<?php

namespace App\Repositories\Tenant;

use App\Models\Tenant;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TenantRepository
{
    private const SORTABLE = ['name', 'slug', 'status', 'created_at'];

    /**
     * Paginated tenant list for the platform admin (cross-tenant).
     *
     * @param  array<string, mixed>  $params
     */
    public function paginateForIndex(array $params): LengthAwarePaginator
    {
        $sort = in_array($params['sort'] ?? '', self::SORTABLE, true) ? $params['sort'] : 'created_at';
        $dir = ($params['dir'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = min((int) ($params['per_page'] ?? 15), 100);

        return Tenant::query()
            ->withCount('employees')
            ->with('subscription')
            ->when($params['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($params['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString();
    }

    public function loadDetail(Tenant $tenant): Tenant
    {
        return $tenant->loadCount('employees')->load('subscription');
    }
}
