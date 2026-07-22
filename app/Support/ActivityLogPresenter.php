<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Activitylog\Models\Activity;

/**
 * يحوّل صفوف سجل النشاط إلى عرض عربي منظم للمستخدم.
 */
final class ActivityLogPresenter
{
    /** @var array<string, string> */
    private const LOG_LABELS = [
        'http' => 'طلبات النظام',
        'auth' => 'المصادقة',
        'users' => 'المستخدمون',
        'projects' => 'المشاريع',
        'default' => 'عام',
    ];

    /** @var array<string, string> */
    private const EVENT_LABELS = [
        'created' => 'إضافة',
        'updated' => 'تعديل',
        'deleted' => 'حذف',
        'GET' => 'عرض',
        'HEAD' => 'عرض',
        'POST' => 'تنفيذ',
        'PUT' => 'تعديل',
        'PATCH' => 'تعديل',
        'DELETE' => 'حذف',
    ];

    /** @var array<string, string> */
    private const METHOD_ACTION = [
        'GET' => 'عرض',
        'HEAD' => 'عرض',
        'POST' => 'إضافة / تنفيذ',
        'PUT' => 'تعديل',
        'PATCH' => 'تعديل',
        'DELETE' => 'حذف',
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

    /** @var array<string, string> */
    private const ACTION_SUFFIX = [
        'index' => 'قائمة',
        'create' => 'نموذج إضافة',
        'store' => 'حفظ جديد',
        'show' => 'تفاصيل',
        'edit' => 'نموذج تعديل',
        'update' => 'حفظ التعديل',
        'destroy' => 'حذف',
        'mine' => 'مهامي',
        'sales' => 'المبيعات',
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

    public function eventLabel(?string $event): string
    {
        $key = (string) $event;
        if ($key === '') {
            return '—';
        }

        return self::EVENT_LABELS[$key] ?? self::EVENT_LABELS[strtoupper($key)] ?? $key;
    }

    public function methodBadgeClass(?string $method): string
    {
        return match (strtoupper((string) $method)) {
            'GET', 'HEAD' => 'text-bg-secondary',
            'POST' => 'text-bg-primary',
            'PUT', 'PATCH' => 'text-bg-warning text-dark',
            'DELETE' => 'text-bg-danger',
            default => 'text-bg-light text-dark border',
        };
    }

    public function statusBadgeClass(mixed $status): string
    {
        $st = (int) $status;
        if ($st >= 500) {
            return 'text-bg-danger';
        }
        if ($st >= 400) {
            return 'text-bg-warning text-dark';
        }
        if ($st >= 300) {
            return 'text-bg-info text-dark';
        }

        return 'text-bg-success';
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
            return $description !== '' ? $description : 'حدث مصادقة';
        }

        if ($logName === 'http') {
            $route = isset($props['route']) ? (string) $props['route'] : null;
            $method = strtoupper((string) ($props['method'] ?? $activity->event ?? 'GET'));
            $action = self::METHOD_ACTION[$method] ?? $method;
            $module = $this->moduleLabel($route);
            $suffix = $this->routeActionSuffix($route);

            if ($route === 'logout') {
                return 'تسجيل خروج';
            }
            if ($route === 'login' || $route === 'login.store') {
                return 'محاولة تسجيل دخول';
            }

            $parts = array_filter([$action, $module, $suffix !== '' && $suffix !== $module ? $suffix : null]);

            return implode(' — ', $parts) ?: ($description !== '' ? $description : 'طلب نظام');
        }

        $event = $this->eventLabel($activity->event);
        $subject = $this->subjectLabel($activity->subject_type);
        if (in_array((string) $activity->event, ['created', 'updated', 'deleted'], true)) {
            return trim($event.' '.$subject);
        }

        if ($description !== '' && ! preg_match('/^(GET|POST|PUT|PATCH|DELETE)\s/i', $description)) {
            return $description;
        }

        return $this->logLabel($logName);
    }

    /**
     * وصف عربي قصير يُحفظ عند تسجيل طلب HTTP.
     */
    public function httpDescription(string $method, ?string $routeName, string $path): string
    {
        $method = strtoupper($method);
        $action = self::METHOD_ACTION[$method] ?? $method;
        $module = $routeName ? $this->moduleLabel($routeName) : null;
        $suffix = $this->routeActionSuffix($routeName);

        if ($routeName === 'logout') {
            return 'تسجيل خروج';
        }
        if (in_array($routeName, ['login', 'login.store'], true)) {
            return 'محاولة تسجيل دخول';
        }

        if ($module && $module !== '—') {
            $parts = array_filter([$action, $module, $suffix !== '' && $suffix !== $module ? $suffix : null]);

            return implode(' — ', $parts);
        }

        return trim($action.' '.($path !== '' ? $path : 'طلب'));
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
     * @return array{
     *     headline: string,
     *     log_label: string,
     *     log_badge: string,
     *     event_label: string,
     *     is_http: bool,
     *     method: ?string,
     *     method_badge: string,
     *     status: mixed,
     *     status_badge: string,
     *     module: string,
     *     route: ?string,
     *     path: ?string,
     *     ip: ?string,
     *     subject_label: string,
     *     subject_name: ?string,
     *     changed_fields: list<array{key: string, label: string, old: string, new: string}>,
     *     has_technical: bool,
     *     technical_json: string,
     *     time_absolute: string,
     *     time_relative: string
     * }
     */
    public function present(Activity $activity): array
    {
        $props = $this->properties($activity);
        $isHttp = ($activity->log_name ?? '') === 'http';
        $method = $isHttp
            ? strtoupper((string) ($props['method'] ?? $activity->event ?? ''))
            : null;
        $status = $props['status'] ?? null;
        $route = isset($props['route']) ? (string) $props['route'] : null;
        $path = isset($props['path']) ? (string) $props['path'] : null;
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

        $technical = $props;
        unset($technical['attributes'], $technical['old']);

        return [
            'headline' => $this->headline($activity),
            'log_label' => $this->logLabel($activity->log_name),
            'log_badge' => $this->logBadgeClass($activity->log_name),
            'event_label' => $this->eventLabel($activity->event),
            'is_http' => $isHttp,
            'method' => $method !== '' ? $method : null,
            'method_badge' => $this->methodBadgeClass($method),
            'status' => $status,
            'status_badge' => $this->statusBadgeClass($status),
            'module' => $this->moduleLabel($route),
            'route' => $route,
            'path' => $path,
            'ip' => isset($props['ip']) ? (string) $props['ip'] : null,
            'subject_label' => $activity->subject_type
                ? $this->subjectLabel($activity->subject_type)
                : ($isHttp ? 'طلب ويب' : '—'),
            'subject_name' => $subjectName,
            'changed_fields' => $changed,
            'has_technical' => $technical !== [],
            'technical_json' => json_encode($props, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '{}',
            'time_absolute' => $created ? $created->format('Y-m-d H:i:s') : '—',
            'time_date' => $created ? $created->format('Y-m-d') : '—',
            'time_clock' => $created ? $created->format('H:i:s') : '—',
            'time_relative' => $relative,
        ];
    }

    private function routeActionSuffix(?string $routeName): string
    {
        if ($routeName === null || $routeName === '') {
            return '';
        }
        $parts = explode('.', $routeName);
        $last = end($parts);
        if ($last === false || count($parts) < 2) {
            return '';
        }

        return self::ACTION_SUFFIX[$last] ?? '';
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
