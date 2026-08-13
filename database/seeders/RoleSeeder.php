<?php

namespace Database\Seeders;

use App\Enums\RoleEnum;
use App\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        // 1. حدد الـ Guard الصحيح الخاص بالـ API (مهم جداً)
        $guardName = 'api'; // أو 'sanctum' حسب إعداداتك

        // 2. تفريغ الكاش الخاص بـ Spatie لتجنب أي مشاكل عند عمل Seed
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // 3. إنشاء جميع الصلاحيات من الـ Enum
        foreach (PermissionEnum::cases() as $permission) {
            Permission::firstOrCreate([
                'name' => $permission->value,
                'guard_name' => $guardName
            ]);
        }

        // 4. إنشاء جميع الأدوار من الـ Enum
        foreach (RoleEnum::cases() as $role) {
            Role::firstOrCreate([
                'name' => $role->value,
                'guard_name' => $guardName
            ]);
        }

        // ==========================================
        // 5. توزيع الصلاحيات على الأدوار (Role Bindings)
        // ==========================================

        // إعطاء السكرتير الصلاحيات الخاصة به
        $secretaryRole = Role::where('name', RoleEnum::SECRETARY->value)->where('guard_name', $guardName)->first();
        if ($secretaryRole) {
            $permissions = Permission::where('guard_name', $guardName)
                ->whereIn('name', [PermissionEnum::CREATE->value])
                ->get();
            $secretaryRole->givePermissionTo($permissions);
        }

        // (اختياري) إعطاء الأدمن جميع الصلاحيات تلقائياً
        $adminRole = Role::where('name', RoleEnum::ADMIN->value)->where('guard_name', $guardName)->first();
        if ($adminRole) {
            $adminRole->givePermissionTo(Permission::where('guard_name', $guardName)->get());
        }
    }
}
