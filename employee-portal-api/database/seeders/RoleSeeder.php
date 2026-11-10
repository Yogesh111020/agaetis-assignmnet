<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Manager', 'Supervisor', 'Employee'] as $name) {
            Role::firstOrCreate(['name' => $name]);
        }
    }
}
