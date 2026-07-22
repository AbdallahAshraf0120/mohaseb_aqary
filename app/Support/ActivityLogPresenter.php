<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * يحوّل صفوف سجل النشاط إلى عرض عربي واضح للعميل (بدون مصطلحات تقنية).
 */
final class ActivityLogPresenter
{
    /** @var array<string, string> */
    private const LOG_LABELS = [
        'http' => 'عملية',
        'auth' => 'دخول وخروج',
        'users' => 'المستخدمون',
        'projects' => 'المشاريع',
        'default' => 'عام',
    ];

    /** @var array<string, string> */
    private const ROLE_LABELS = [
        'admin' => 'مدير النظام',
        'accountant' => 'محاسب',
        'sales' => 'مبيعات',
        'viewer' => 'عرض فقط',
    ];

    /** @var array<string, string> */
    private const MODULE_LABELS = [
        'home' => 'الرئيسية',
        'login' => 'تسجيل الدخول',
        'logout' => 'تسجيل الخروج',
        'projects' => 'المشاريع',
        'areas' => 'المناطق',
        'shareholders' => 'المساهمين',
        'properties' => 'العقارات',
        'clients' => 'العملاء',
        'contracts' => 'العقود',
        'sales' => 'المبيعات',
        'revenues' => 'التحصيل',
        'expenses' => 'المصروفات',
        'expense-types' => 'أنواع المصروفات',
        'cashbox' => 'الصندوق',
        'land-cashbox' => 'صندوق الأراضي',
        'global-cashbox' => 'الصندوق الشامل',
        'fund-transfers' => 'تحويلات الصناديق',
        'remaining' => 'المتبقي',
        'debts' => 'الذمم',
        'settlements' => 'التصفيات',
        'reports' => 'التقارير',
        'settings' => 'الإعدادات',
        'approvals' => 'طلبات الاعتماد',
        'users' => 'المستخدمون',
        'activity-log' => 'سجل النشاط',
        'land-trading' => 'تجارة الأراضي',
        'crm-leads' => 'العملاء المحتملون',
        'tasks' => 'المهام',
        'site-sketch' => 'كروكي الموقع',
    ];

    /** @var array<string, string> عناوين جاهزة لمسارات شائعة */
    private const ROUTE_HEADLINES = [
        'logout' => 'تسجيل خروج من النظام',
        'login' => 'محاولة تسجيل دخول',
        'login.store' => 'محاولة تسجيل دخول',
        'sales.store' => 'تم تسجيل بيعة جديدة',
        'sales.update' => 'تم تعديل بيعة',
        'sales.destroy' => 'تم حذف بيعة',
        'revenues.store' => 'تم تسجيل تحصيل',
        'revenues.update' => 'تم تعديل تحصيل',
        'revenues.destroy' => 'تم حذف تحصيل',
        'expenses.store' => 'تم تسجيل مصروف',
        'expenses.update' => 'تم تعديل مصروف',
        'expenses.destroy' => 'تم حذف مصروف',
        'clients.store' => 'تم إضافة عميل',
        'clients.update' => 'تم تعديل عميل',
        'clients.destroy' => 'تم حذف عميل',
        'contracts.store' => 'تم إضافة عقد',
        'contracts.update' => 'تم تعديل عقد',
        'contracts.destroy' => 'تم حذف عقد',
        'properties.store' => 'تم إضافة عقار',
        'properties.update' => 'تم تعديل عقار',
        'properties.destroy' => 'تم حذف عقار',
        'projects.store' => 'تم إضافة مشروع',
        'projects.update' => 'تم تعديل مشروع',
        'projects.destroy' => 'تم حذف مشروع',
        'shareholders.store' => 'تم إضافة مساهم',
        'shareholders.update' => 'تم تعديل مساهم',
        'shareholders.destroy' => 'تم حذف مساهم',
        'users.store' => 'تم إضافة مستخدم',
        'users.update' => 'تم تعديل مستخدم',
        'users.destroy' => 'تم حذف مستخدم',
        'debts.store' => 'تم تسجيل ذمة',
        'debts.update' => 'تم تعديل ذمة',
        'debts.destroy' => 'تم حذف ذمة',
        'cashbox.store' => 'تم تسجيل حركة صندوق',
        'land-cashbox.store' => 'تم تسجيل حركة صندوق أراضي',
        'fund-transfers.store' => 'تم تنفيذ تحويل بين الصناديق',
        'approvals.approve' => 'تم اعتماد عملية',
        'approvals.reject' => 'تم رفض عملية',
        'crm-leads.store' => 'تم إضافة عميل محتمل',
        'crm-leads.update' => 'تم تعديل عميل محتمل',
        'tasks.store' => 'تم إضافة مهمة',
        'tasks.update' => 'تم تعديل مهمة',
        'land-trading.store' => 'تم تسجيل عملية أرض',
        'land-trading.payments.store' => 'تم تسجيل دفعة أرض',
    ];

    /** @var array<string, string> */
    private const SUBJECT_LABELS = [
        'User' => 'مستخدم',
        'Project' => 'مشروع',
        'Property' => 'عقار',
        'Client' => 'عميل',
        'Contract' => 'عقد',
        'Sale' => 'بيعة',
        'Revenue' => 'تحصيل',
        'Expense' => 'مصروف',
        'Shareholder' => 'مساهم',
        'Debt' => 'ذمة',
        'DebtPayment' => 'سداد ذمة',
        'TreasuryTransaction' => 'حركة صندوق',
        'CrmLead' => 'عميل محتمل',
        'Task' => 'مهمة',
        'LandParcel' => 'أرض',
        'LandParcelPayment' => 'دفعة أرض',
    ];

    /** @var array<string, string> */
    private const FIELD_LABELS = [
        'name' => 'الاسم',
        'email' => 'البريد',
        'role' => 'الدور',
        'password' => 'كلمة المرور',
        'extra_permissions' => 'صلاحيات إضافية',
        'code' => 'الكود',
        'capital' => 'رأس المال',
        'planned_capital' => 'رأس المال المخطط',
        'actual_capital' => 'رأس المال الفعلي',
        'is_active' => 'نشط',
        'is_draft' => 'مسودة',
        'phone' => 'الهاتف',
        'amount' => 'المبلغ',
        'notes' => 'ملاحظات',
        'description' => 'الوصف',
        'status' => 'الحالة',
        'approval_status' => 'حالة الاعتماد',
        'sale_price' => 'سعر البيع',
        'down_payment' => 'المقدم',
        'payment_type' => 'نوع السداد',
        'broker_name' => 'البروكر',
        'category' => 'الفئة',
        'payment_method' => 'طريقة الدفع',
        'paid_at' => 'تاريخ الدفع',
        'sale_date' => 'تاريخ البيعة',
        'spent_at' => 'تاريخ الصرف',
        'received_by_shareholder_id' => 'دخل حساب المساهم',
    ];

    /** @return array<string, string> */
    public function logNameOptions(): array
    {
        return self::LOG_LABELS;
    }

    public function logLabel(?string $logName): string
    {
        $key = (string) $logName;

        return self::LOG_LABELS[$key] ?? ($key !== '' ? $key : 'عام');
    }

    public function logBadgeClass(?string $logName): string
    {
        return match ($logName) {
            'http' => 'text-bg-primary',
            'auth' => 'text-bg-dark',
            'users' => 'text-bg-success',
            'projects' => 'text-bg-info',
            'default' => 'text-bg-secondary',
            default => 'text-bg-light text-dark border',
        };
    }

    public function roleLabel(?string $role): string
    {
        $key = (string) $role;
        if ($key === '') {
            return '';
        }

        return self::ROLE_LABELS[$key] ?? $key;
    }

    public function moduleLabel(?string $routeName): string
    {
        if ($routeName === null || $routeName === '') {
            return '—';
        }

        $prefix = explode('.', $routeName)[0] ?? $routeName;

        return self::MODULE_LABELS[$prefix] ?? $prefix;
    }

    public function fieldLabel(string $field): string
    {
        return self::FIELD_LABELS[$field] ?? $field;
    }

    public function subjectLabel(?string $subjectType): string
    {
        if ($subjectType === null || $subjectType === '') {
            return '—';
        }

        $base = class_basename($subjectType);

        return self::SUBJECT_LABELS[$base] ?? $base;
    }

    /**
     * نوع العملية بلغة العميل: إضافة / تعديل / حذف / عرض / دخول.
     */
    public function actionKind(Activity $activity): string
    {
        $logName = (string) ($activity->log_name ?? '');
        if ($logName === 'auth') {
            $desc = (string) ($activity->description ?? '');
            if (str_contains($desc, 'خروج')) {
                return 'خروج';
            }

            return 'دخول';
        }

        $event = strtolower((string) ($activity->event ?? ''));
        if (in_array($event, ['created'], true)) {
            return 'إضافة';
        }
        if (in_array($event, ['updated'], true)) {
            return 'تعديل';
        }
        if (in_array($event, ['deleted'], true)) {
            return 'حذف';
        }

        $props = $this->properties($activity);
        $method = strtoupper((string) ($props['method'] ?? $activity->event ?? ''));
        $route = isset($props['route']) ? (string) $props['route'] : '';
        $action = $this->routeAction($route);

        return match (true) {
            $action === 'destroy' || $method === 'DELETE' => 'حذف',
            in_array($action, ['update', 'edit'], true) || in_array($method, ['PUT', 'PATCH'], true) => 'تعديل',
            in_array($action, ['store', 'create'], true) || $method === 'POST' => 'إضافة',
            default => 'عرض',
        };
    }

    public function actionKindBadgeClass(string $kind): string
    {
        return match ($kind) {
            'إضافة' => 'text-bg-success',
            'تعديل' => 'text-bg-warning text-dark',
            'حذف' => 'text-bg-danger',
            'دخول', 'خروج' => 'text-bg-dark',
            default => 'text-bg-light text-dark border',
        };
    }

    /**
     * @return array<string, mixed>
     */
    public function properties(Activity $activity): array
    {
        $raw = $activity->properties;
        if ($raw instanceof \Illuminate\Support\Collection) {
            return $raw->toArray();
        }

        return is_array($raw) ? $raw : [];
    }

    public function headline(Activity $activity): string
    {
        $logName = (string) ($activity->log_name ?? '');
        $props = $this->properties($activity);
        $description = trim((string) ($activity->description ?? ''));

        if ($logName === 'auth') {
            if ($description !== '' && ! preg_match('/^(GET|POST|PUT|PATCH|DELETE)\s/i', $description)) {
                return $description;
            }

            return 'حدث دخول أو خروج';
        }

        if ($logName === 'http') {
            $route = isset($props['route']) ? (string) $props['route'] : null;

            return $this->clientHeadlineForRoute($route, strtoupper((string) ($props['method'] ?? $activity->event ?? 'GET')));
        }

        if (in_array((string) $activity->event, ['created', 'updated', 'deleted'], true)) {
            $subject = $this->subjectLabel($activity->subject_type);

            return match ((string) $activity->event) {
                'created' => 'تم إضافة '.$subject,
                'updated' => 'تم تعديل '.$subject,
                'deleted' => 'تم حذف '.$subject,
                default => $subject,
            };
        }

        if ($description !== '' && ! preg_match('/^(GET|POST|PUT|PATCH|DELETE)\s/i', $description)) {
            // وصف قديم بصيغة «إضافة / تنفيذ — …» نحوّله لجملة أوضح إن أمكن
            if (str_contains($description, ' — ')) {
                $route = isset($props['route']) ? (string) $props['route'] : null;
                if ($route) {
                    return $this->clientHeadlineForRoute($route, 'POST');
                }
            }

            return $description;
        }

        return $this->logLabel($logName);
    }

    /**
     * وصف عربي قصير يُحفظ عند تسجيل طلب HTTP.
     */
    public function httpDescription(string $method, ?string $routeName, string $path): string
    {
        return $this->clientHeadlineForRoute($routeName, strtoupper($method));
    }

    /**
     * @return list<array{key: string, label: string, old: string, new: string}>
     */
    public function changedFields(Activity $activity): array
    {
        $props = $this->properties($activity);
        $attrs = is_array($props['attributes'] ?? null) ? $props['attributes'] : [];
        $old = is_array($props['old'] ?? null) ? $props['old'] : [];

        $keys = array_values(array_unique(array_merge(array_keys($attrs), array_keys($old))));
        $rows = [];
        foreach ($keys as $key) {
            $key = (string) $key;
            $before = array_key_exists($key, $old) ? $old[$key] : null;
            $after = array_key_exists($key, $attrs) ? $attrs[$key] : null;
            if ($this->stringifyValue($before) === $this->stringifyValue($after)) {
                continue;
            }
            $rows[] = [
                'key' => $key,
                'label' => $this->fieldLabel($key),
                'old' => $this->stringifyValue($before),
                'new' => $this->stringifyValue($after),
            ];
        }

        return $rows;
    }

    /**
     * @return array<string, mixed>
     */
    public function present(Activity $activity): array
    {
        $props = $this->properties($activity);
        $isHttp = ($activity->log_name ?? '') === 'http';
        $status = isset($props['status']) ? (int) $props['status'] : null;
        $route = isset($props['route']) ? (string) $props['route'] : null;
        $changed = $this->changedFields($activity);
        $created = $activity->created_at
            ? Carbon::parse($activity->created_at)->timezone(config('app.timezone'))
            : null;
        $relative = '—';
        if ($created) {
            $previousLocale = Carbon::getLocale();
            Carbon::setLocale('ar');
            $relative = $created->diffForHumans();
            Carbon::setLocale($previousLocale);
        }

        $subjectName = null;
        if ($activity->subject) {
            $subjectName = $activity->subject->getAttribute('name')
                ?? $activity->subject->getAttribute('title')
                ?? $activity->subject->getAttribute('email');
            if (is_string($subjectName)) {
                $subjectName = Str::limit($subjectName, 48);
            } else {
                $subjectName = null;
            }
        }

        $actionKind = $this->actionKind($activity);
        $module = $this->moduleLabel($route);
        $failed = $status !== null && $status >= 400;

        return [
            'headline' => $this->headline($activity),
            'log_label' => $this->logLabel($activity->log_name),
            'log_badge' => $this->logBadgeClass($activity->log_name),
            'action_kind' => $actionKind,
            'action_kind_badge' => $this->actionKindBadgeClass($actionKind),
            'is_http' => $isHttp,
            'module' => $module,
            'failed' => $failed,
            'result_label' => $failed ? 'لم تكتمل' : null,
            'subject_label' => $activity->subject_type
                ? $this->subjectLabel($activity->subject_type)
                : ($isHttp && $module !== '—' ? $module : null),
            'subject_name' => $subjectName,
            'changed_fields' => $changed,
            'time_date' => $created ? $created->format('Y-m-d') : '—',
            'time_clock' => $created ? $created->format('H:i') : '—',
            'time_relative' => $relative,
        ];
    }

    private function clientHeadlineForRoute(?string $routeName, string $method): string
    {
        if ($routeName !== null && $routeName !== '' && isset(self::ROUTE_HEADLINES[$routeName])) {
            return self::ROUTE_HEADLINES[$routeName];
        }

        $module = $this->moduleLabel($routeName);
        $action = $this->routeAction($routeName);

        if ($routeName === 'logout') {
            return 'تسجيل خروج من النظام';
        }

        if ($module === '—' || $module === '') {
            return match (strtoupper($method)) {
                'DELETE' => 'تم حذف سجل',
                'PUT', 'PATCH' => 'تم تعديل سجل',
                'POST' => 'تم تنفيذ عملية',
                default => 'تم عرض صفحة',
            };
        }

        return match ($action) {
            'store', 'create' => 'تم إضافة سجل في '.$module,
            'update', 'edit' => 'تم تعديل سجل في '.$module,
            'destroy' => 'تم حذف سجل من '.$module,
            'show' => 'عرض تفاصيل من '.$module,
            'index', 'mine' => 'عرض قائمة '.$module,
            'approve' => 'تم اعتماد عملية في '.$module,
            'reject' => 'تم رفض عملية في '.$module,
            default => match (strtoupper($method)) {
                'DELETE' => 'تم حذف سجل من '.$module,
                'PUT', 'PATCH' => 'تم تعديل سجل في '.$module,
                'POST' => 'تم تنفيذ عملية في '.$module,
                default => 'عرض '.$module,
            },
        };
    }

    private function routeAction(?string $routeName): string
    {
        if ($routeName === null || $routeName === '') {
            return '';
        }
        $parts = explode('.', $routeName);
        $last = end($parts);

        return is_string($last) ? $last : '';
    }

    private function stringifyValue(mixed $value): string
    {
        if ($value === null) {
            return '—';
        }
        if (is_bool($value)) {
            return $value ? 'نعم' : 'لا';
        }
        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE);

            return $json !== false ? $json : '—';
        }

        $str = trim((string) $value);

        return $str !== '' ? $str : '—';
    }
}
