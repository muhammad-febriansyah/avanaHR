<?php

namespace App\Actions\Tenant;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class DeleteTenantAction
{
    public function handle(Tenant $tenant): void
    {
        DB::transaction(fn () => $tenant->delete());
    }
}
