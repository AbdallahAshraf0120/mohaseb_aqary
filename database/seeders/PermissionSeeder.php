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
            'activity_log.view' => 'عرض سجل النشاط (تدقيق)',
        ];

        // صلاحيات دقيقة لكل Route/Action (متوافقة مع الصلاحيات القديمة view/manage).
        /** @var array<string, string> $routeMap */
        $routeMap = config('route-permissions', []);
        foreach (array_values($routeMap) as $slug) {
            if (! is_string($slug) || $slug === '') {
                continue;
            }
            if (! array_key_exists($slug, $labels)) {
                $labels[$slug] = self::labelForFineGrainedSlug($slug);
            }
        }

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
            'activity_log.view',
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

    private static function labelForFineGrainedSlug(string $slug): string
    {
        $moduleLabels = [
            'home' => 'الصفحة الرئيسية',
            'login' => 'تسجيل الدخول',
            'logout' => 'تسجيل الخروج',
            'dashboard' => 'لوحة التحكم',
            'projects' => 'المشاريع',
            'properties' => 'العقارات',
            'areas' => 'المناطق',
            'facings' => 'الوجهات',
            'lands' => 'الأراضي',
            'shareholders' => 'المساهمين',
            'sales' => 'المبيعات',
            'clients' => 'العملاء',
            'contracts' => 'العقود',
            'revenues' => 'التحصيلات',
            'expenses' => 'المصروفات',
            'cashbox' => 'الصندوق',
            'approvals' => 'طلبات الاعتماد',
            'debts' => 'الذمم الدائنة',
            'remaining' => 'المتبقي',
            'settlements' => 'التصفيات',
            'reports' => 'التقارير',
            'settings' => 'الإعدادات',
            'users' => 'المستخدمين',
            'activity-log' => 'سجل النشاط',
            'storage' => 'الملفات',
        ];

        $actionLabels = [
            'index' => 'عرض القائمة',
            'show' => 'عرض التفاصيل',
            'create' => 'فتح صفحة الإنشاء',
            'store' => 'حفظ جديد',
            'edit' => 'فتح صفحة التعديل',
            'update' => 'حفظ التعديل',
            'destroy' => 'حذف',
            'export' => 'تصدير',
            'word' => 'تصدير Word',
            'draft' => 'تحويل إلى مسودة',
            'restore' => 'استرجاع من مسودة',
            'landing' => 'فتح المشروع',
            'contract-template' => 'تنزيل قالب العقد',
            'pay-from-cashbox' => 'سداد من الصندوق',
            'local' => 'عرض ملف',
            'local.upload' => 'رفع ملف',
            'approve' => 'اعتماد',
            'reject' => 'رفض',
        ];

        // حالات بدون نقطة (home/login/logout/dashboard)
        if (! str_contains($slug, '.')) {
            return $moduleLabels[$slug] ?? ('صلاحية: '.$slug);
        }

        $parts = explode('.', $slug);
        $module = array_shift($parts) ?? '';
        $action = implode('.', $parts);

        $moduleAr = $moduleLabels[$module] ?? $module;
        $actionAr = $actionLabels[$action] ?? null;

        if ($actionAr === null && count($parts) === 1) {
            $actionAr = $actionLabels[$parts[0]] ?? null;
        }

        if ($actionAr === null) {
            return 'صلاحية: '.$slug;
        }

        // صياغة مختصرة وواضحة في شاشة الصلاحيات
        return $actionAr.' - '.$moduleAr;
    }
}
