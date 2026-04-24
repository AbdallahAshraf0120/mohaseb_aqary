<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $labels = [
            'dashboard.view' => 'عرض لوحة التحكم',
            'projects.view' => 'عرض المشاريع والتبديل بينها',
            'projects.manage' => 'إدارة المشاريع (إنشاء، تعديل، حذف، مسودة)',
            'properties.view' => 'عرض العقارات',
            'properties.manage' => 'إدارة العقارات (إنشاء وتعديل وحذف)',
            'areas.manage' => 'إدارة المناطق',
            'facings.manage' => 'إدارة الوجهات',
            'lands.manage' => 'إدارة الأراضي',
            'shareholders.view' => 'عرض المساهمين',
            'shareholders.manage' => 'إدارة المساهمين',
            'sales.view' => 'عرض المبيعات',
            'sales.manage' => 'إدارة المبيعات',
            'clients.view' => 'عرض العملاء',
            'contracts.view' => 'عرض العقود',
            'revenues.view' => 'عرض التحصيلات',
            'revenues.manage' => 'تسجيل وتعديل وحذف التحصيلات',
            'expenses.view' => 'عرض المصروفات',
            'expenses.manage' => 'تسجيل وحذف المصروفات',
            'cashbox.view' => 'عرض الصندوق',
            'cashbox.manage' => 'تسجيل حركات الصندوق اليدوية',
            'debts.view' => 'عرض الذمم الدائنة',
            'debts.manage' => 'إدارة الذمم وسداد الصندوق',
            'remaining.view' => 'عرض المتبقي',
            'settlements.view' => 'عرض التصفيات',
            'reports.view' => 'عرض التقارير',
            'reports.export' => 'تصدير التقارير',
            'settings.manage' => 'إعدادات المشروع',
            'users.view' => 'عرض قائمة المستخدمين',
            'users.manage' => 'إدارة المستخدمين (إنشاء، تعديل، حذف، أدوار وصلاحيات إضافية)',
        ];

        foreach ($labels as $slug => $label) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                ['label' => $label]
            );
        }

        RolePermission::query()->delete();

        $viewer = [
            'dashboard.view',
            'projects.view',
            'properties.view',
            'shareholders.view',
            'sales.view',
            'clients.view',
            'contracts.view',
            'revenues.view',
            'expenses.view',
            'cashbox.view',
            'debts.view',
            'remaining.view',
            'settlements.view',
            'reports.view',
        ];

        $sales = array_merge($viewer, [
            'sales.manage',
            'revenues.manage',
        ]);

        $accountant = array_merge($viewer, [
            'properties.manage',
            'areas.manage',
            'facings.manage',
            'lands.manage',
            'shareholders.manage',
            'revenues.manage',
            'expenses.manage',
            'cashbox.manage',
            'debts.manage',
            'reports.export',
        ]);

        $admin = array_keys($labels);

        foreach (['viewer' => $viewer, 'sales' => $sales, 'accountant' => $accountant, 'admin' => $admin] as $role => $slugs) {
            foreach ($slugs as $slug) {
                RolePermission::query()->create([
                    'role' => $role,
                    'permission_slug' => $slug,
                ]);
            }
        }
    }
}
