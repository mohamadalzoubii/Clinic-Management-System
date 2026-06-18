<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate(['name' => $role->value]);
        }

        $permission = \Spatie\Permission\Models\Permission::firstOrCreate([
            'name' => \App\Enums\PermissionEnum::CREATE->value,
            'guard_name' => 'web'
        ]);


        $secretaryRole = \Spatie\Permission\Models\Role::where('name', RoleEnum::SECRETARY->value)->first();
        if ($secretaryRole) {
            $secretaryRole->givePermissionTo($permission);
        }
    }
}
