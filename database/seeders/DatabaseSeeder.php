<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Platform super-admin (cross-tenant, no tenant_id).
        User::factory()->create([
            'name' => 'Super Admin',
            'email' => 'superadmin@avanahr.id',
            'is_super_admin' => true,
        ]);

        $this->call(DemoTenantSeeder::class);
    }
}
