<?php

namespace App\Support;

use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * القائمة الجانبية: مجموعات حسب الشغل، بتتفلتر حسب صلاحيات المستخدم.
 * الروابط الخاصة بمشروع بتستخدم المشروع الحالي.
 */
class Navigation
{
    /**
     * تعريف العناصر: route, label, icon, perm, active (أنماط), project (هل محتاج مشروع), create (route), create_perm, badge.
     *
     * @return list<array{title: ?string, icon: ?string, items: list<array<string, mixed>>}>
     */
    private static function definition(bool $canManageTasks): array
    {
        return [
            ['title' => null, 'icon' => null, 'items' => [
                ['route' => 'dashboard', 'label' => 'لوحة التحكم', 'icon' => 'fa-gauge-high', 'perm' => 'dashboard.view', 'active' => ['dashboard'], 'project' => true],
                ['route' => 'approvals.index', 'label' => 'طلبات الاعتماد', 'icon' => 'fa-circle-check', 'perm' => 'approvals.index', 'active' => ['approvals.*'], 'badge' => 'approvals'],
            ]],
            ['title' => 'المبيعات والعملاء', 'icon' => 'fa-cart-shopping', 'items' => [
                ['route' => 'sales.index', 'label' => 'المبيعات', 'icon' => 'fa-file-contract', 'perm' => 'sales.view', 'active' => ['sales.*'], 'project' => true, 'create' => 'sales.create', 'create_perm' => 'sales.manage'],
                ['route' => 'reservations.index', 'label' => 'الحجوزات', 'icon' => 'fa-bookmark', 'perm' => 'sales.view', 'active' => ['reservations.*'], 'project' => true],
                ['route' => 'clients.index', 'label' => 'العملاء', 'icon' => 'fa-users', 'perm' => 'clients.view', 'active' => ['clients.*'], 'project' => true],
                ['route' => 'contracts.index', 'label' => 'العقود', 'icon' => 'fa-file-signature', 'perm' => 'contracts.view', 'active' => ['contracts.*'], 'project' => true],
                ['route' => 'site-sketch.index', 'label' => 'مخطط الوحدات', 'icon' => 'fa-table-cells', 'perm' => 'site-sketch.view', 'active' => ['site-sketch.*']],
                ['route' => 'crm-leads.index', 'label' => 'العملاء المحتملين', 'icon' => 'fa-address-book', 'perm' => 'crm.view', 'active' => ['crm-leads.*']],
            ]],
            ['title' => 'التحصيل والأقساط', 'icon' => 'fa-hand-holding-dollar', 'items' => [
                ['route' => 'revenues.index', 'label' => 'التحصيلات', 'icon' => 'fa-money-bill-trend-up', 'perm' => 'revenues.view', 'active' => ['revenues.*'], 'project' => true, 'create' => 'revenues.create', 'create_perm' => 'revenues.manage'],
                ['route' => 'remaining.due', 'label' => 'الأقساط المتأخرة', 'icon' => 'fa-calendar-xmark', 'perm' => 'remaining.view', 'active' => ['remaining.due'], 'project' => true],
                ['route' => 'remaining.index', 'label' => 'المتبقي على العملاء', 'icon' => 'fa-hourglass-half', 'perm' => 'remaining.view', 'active' => ['remaining.index'], 'project' => true],
                ['route' => 'cheques.index', 'label' => 'الشيكات', 'icon' => 'fa-money-check', 'perm' => 'revenues.view', 'active' => ['cheques.*'], 'project' => true],
            ]],
            ['title' => 'المصروفات والموردين', 'icon' => 'fa-file-invoice-dollar', 'items' => [
                ['route' => 'expenses.index', 'label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'perm' => 'expenses.view', 'active' => ['expenses.*'], 'project' => true, 'create' => 'expenses.create', 'create_perm' => 'expenses.manage'],
                ['route' => 'debts.index', 'label' => 'الموردين والذمم', 'icon' => 'fa-scale-balanced', 'perm' => 'debts.view', 'active' => ['debts.*'], 'project' => true, 'create' => 'debts.create', 'create_perm' => 'debts.manage'],
                ['route' => 'expense-types.index', 'label' => 'أنواع المصروفات', 'icon' => 'fa-tags', 'perm' => 'expense-types.manage', 'active' => ['expense-types.*'], 'project' => true],
            ]],
            ['title' => 'الخزينة', 'icon' => 'fa-vault', 'items' => [
                ['route' => 'cashbox.index', 'label' => 'صندوق المشروع', 'icon' => 'fa-cash-register', 'perm' => 'cashbox.view', 'active' => ['cashbox.*'], 'project' => true],
                ['route' => 'global-cashbox.index', 'label' => 'الصندوق الشامل', 'icon' => 'fa-vault', 'perm' => 'cashbox.view', 'active' => ['global-cashbox.*']],
                ['route' => 'fund-transfers.index', 'label' => 'تحويلات بين الصناديق', 'icon' => 'fa-right-left', 'perm' => 'cashbox.view', 'active' => ['fund-transfers.*']],
                ['route' => 'land-cashbox.index', 'label' => 'صندوق الأراضي', 'icon' => 'fa-map-location-dot', 'perm' => 'cashbox.view', 'active' => ['land-cashbox.*']],
            ]],
            ['title' => 'الشركاء والأرباح', 'icon' => 'fa-people-group', 'items' => [
                ['route' => 'settlements.index', 'label' => 'التسويات والأرباح', 'icon' => 'fa-filter-circle-dollar', 'perm' => 'settlements.view', 'active' => ['settlements.*'], 'project' => true],
                ['route' => 'shareholders.index', 'label' => 'المساهمين', 'icon' => 'fa-people-group', 'perm' => 'shareholders.view', 'active' => ['shareholders.*']],
            ]],
            ['title' => 'العقارات والأراضي', 'icon' => 'fa-building', 'items' => [
                ['route' => 'properties.index', 'label' => 'العقارات', 'icon' => 'fa-building', 'perm' => 'properties.view', 'active' => ['properties.*'], 'project' => true, 'create' => 'properties.create', 'create_perm' => 'properties.manage'],
                ['route' => 'areas.index', 'label' => 'المناطق', 'icon' => 'fa-location-dot', 'perm' => 'areas.manage', 'active' => ['areas.*'], 'project' => true],
                ['route' => 'facings.index', 'label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'perm' => 'facings.manage', 'active' => ['facings.*'], 'project' => true],
                ['route' => 'lands.index', 'label' => 'أراضي المشروع', 'icon' => 'fa-map', 'perm' => 'lands.manage', 'active' => ['lands.*'], 'project' => true],
                ['route' => 'land-trading.index', 'label' => 'تجارة الأراضي', 'icon' => 'fa-map-location', 'perm' => 'land-trading.view', 'active' => ['land-trading.index', 'land-trading.create', 'land-trading.edit', 'land-trading.show']],
                ['route' => 'land-trading.sales', 'label' => 'مبيعات الأراضي', 'icon' => 'fa-handshake', 'perm' => 'land-trading.view', 'active' => ['land-trading.sales']],
            ]],
            ['title' => 'التقارير', 'icon' => 'fa-chart-line', 'items' => [
                ['route' => 'reports.index', 'label' => 'التقارير', 'icon' => 'fa-chart-pie', 'perm' => 'reports.view', 'active' => ['reports.*'], 'project' => true],
                ['route' => 'import.index', 'label' => 'استيراد من Excel', 'icon' => 'fa-file-import', 'perm' => 'projects.manage', 'active' => ['import.*'], 'project' => true],
            ]],
            ['title' => 'المتابعة', 'icon' => 'fa-list-check', 'items' => array_values(array_filter([
                $canManageTasks ? ['route' => 'tasks.index', 'label' => 'كل المهام', 'icon' => 'fa-list-check', 'perm' => 'tasks.view', 'active' => ['tasks.index', 'tasks.create', 'tasks.show', 'tasks.edit']] : null,
                ['route' => 'tasks.mine', 'label' => 'مهامي', 'icon' => 'fa-user-check', 'perm' => 'tasks.view', 'active' => $canManageTasks ? ['tasks.mine'] : ['tasks.*']],
            ]))],
            ['title' => 'الإدارة', 'icon' => 'fa-gear', 'items' => [
                ['route' => 'projects.index', 'label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'perm' => 'projects.manage', 'active' => ['projects.*']],
                ['route' => 'settings.edit', 'label' => 'إعدادات المشروع', 'icon' => 'fa-sliders', 'perm' => 'settings.manage', 'active' => ['settings.*'], 'project' => true],
                ['route' => 'users.index', 'label' => 'المستخدمين والصلاحيات', 'icon' => 'fa-users-gear', 'perm' => 'users.view', 'active' => ['users.*']],
                ['route' => 'activity-log.index', 'label' => 'سجل النشاط', 'icon' => 'fa-clipboard-list', 'perm' => 'activity_log.view', 'active' => ['activity-log.*']],
                ['route' => 'website.index', 'label' => 'الموقع الإلكتروني', 'icon' => 'fa-globe', 'perm' => 'website.manage', 'active' => ['website.*']],
            ]],
        ];
    }

    /**
     * @return list<array{title: ?string, icon: ?string, open: bool, items: list<array<string, mixed>>}>
     */
    public static function groups(?User $user, ?Project $project, int $pendingApprovals = 0): array
    {
        if (! $user) {
            return [];
        }
        $out = [];
        foreach (self::definition($user->can('tasks.manage')) as $group) {
            $items = [];
            foreach ($group['items'] as $item) {
                if (! Route::has($item['route']) || ! $user->can($item['perm'])) {
                    continue;
                }
                $needsProject = (bool) ($item['project'] ?? false);
                if ($needsProject && ! $project) {
                    continue;
                }
                $params = $needsProject ? [$project] : [];
                $active = collect($item['active'])->contains(fn (string $p) => request()->routeIs($p));
                $create = null;
                if (! empty($item['create']) && Route::has($item['create']) && $user->can($item['create_perm'] ?? $item['perm'])) {
                    $create = route($item['create'], $params);
                }
                $items[] = [
                    'label' => $item['label'],
                    'icon' => $item['icon'],
                    'url' => route($item['route'], $params),
                    'active' => $active,
                    'create_url' => $create,
                    'badge' => ($item['badge'] ?? null) === 'approvals' && $pendingApprovals > 0 ? $pendingApprovals : null,
                ];
            }
            if ($items !== []) {
                $out[] = [
                    'title' => $group['title'],
                    'icon' => $group['icon'],
                    'open' => collect($items)->contains('active', true),
                    'items' => $items,
                ];
            }
        }

        return $out;
    }
}
