<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

$modules = [
    'role-permission' => ['label' => 'Role & Permission', 'icon' => 'fa-user-shield', 'route' => 'modules.show'],
    'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'modules.show'],
    'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'modules.show'],
    'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'modules.show'],
    'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'modules.show'],
    'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'modules.show'],
    'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'modules.show'],
    'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'modules.show'],
    'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'modules.show'],
    'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'modules.show'],
    'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'modules.show'],
    'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'modules.show'],
    'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'modules.show'],
    'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'modules.show'],
];

Route::get('/', function () use ($modules) {
    return view('demo', [
        'title' => 'Demo | Mohaseb Aqary',
        'pageTitle' => 'Demo النظام',
        'modules' => $modules,
    ]);
})->name('home');

Route::get('/dashboard', function () use ($modules) {
    return view('dashboard', [
        'title' => 'Dashboard | Mohaseb Aqary',
        'pageTitle' => 'Dashboard',
        'modules' => $modules,
    ]);
})->name('dashboard');

Route::get('/demo', function () use ($modules) {
    return view('demo', [
        'title' => 'Demo | Mohaseb Aqary',
        'pageTitle' => 'Demo النظام',
        'modules' => $modules,
    ]);
})->name('demo');

Route::resource('properties', PropertyController::class);

Route::get('/modules/{module}', function (string $module) use ($modules) {
    abort_unless(array_key_exists($module, $modules), 404);

    $wireflow = [
        'role-permission' => [
            'kpis' => [
                ['label' => 'المستخدمون النشطون', 'value' => '8'],
                ['label' => 'الأدوار', 'value' => '4'],
                ['label' => 'الصلاحيات الممنوحة', 'value' => '36'],
            ],
            'filters' => ['بحث بالاسم', 'فلترة حسب الدور', 'حالة الحساب'],
            'rows' => [
                ['مدير النظام', 'Admin', 'صلاحيات كاملة', 'نشط'],
                ['محاسب المشروع', 'Accountant', 'قيود مالية وتقارير', 'نشط'],
                ['مسؤول مبيعات', 'Sales', 'العقود والمبيعات', 'نشط'],
            ],
            'quickActions' => ['إضافة مستخدم', 'إنشاء دور جديد', 'نسخ قالب صلاحيات'],
            'next' => 'shareholders',
        ],
        'shareholders' => [
            'kpis' => [
                ['label' => 'عدد المساهمين', 'value' => '4'],
                ['label' => 'رأس المال', 'value' => '5,000,000 ج.م'],
                ['label' => 'نسبة التغطية', 'value' => '100%'],
            ],
            'filters' => ['بحث بالمساهم', 'فلترة النسبة', 'الحالة'],
            'tableHeaders' => ['اسم المساهم', 'نسبة المساهمة', 'قيمة المساهمة', 'الحالة'],
            'rows' => [
                ['أحمد خالد', '40%', '2,000,000 ج.م', 'نشط'],
                ['محمود عمر', '30%', '1,500,000 ج.م', 'نشط'],
                ['سامي ربيع', '20%', '1,000,000 ج.م', 'نشط'],
                ['نور محمد', '10%', '500,000 ج.م', 'نشط'],
            ],
            'highlights' => [
                ['label' => 'توزيع المساهمات', 'value' => '40% - 30% - 20% - 10%'],
                ['label' => 'أخر تعديل نسب', 'value' => '05-04-2026'],
                ['label' => 'طلبات تحويل حصص', 'value' => '1 طلب قيد المراجعة'],
            ],
            'formFields' => ['اسم المساهم', 'رقم الهوية/السجل', 'نسبة المساهمة %', 'قيمة المساهمة'],
            'quickActions' => ['إضافة مساهم', 'تعديل نسب المساهمة', 'طباعة كشف المساهمين'],
            'next' => 'properties',
        ],
        'properties' => [
            'kpis' => [
                ['label' => 'نوع العقار', 'value' => 'سكني استثماري'],
                ['label' => 'عدد الادوار', 'value' => '6'],
                ['label' => 'شقق لكل دور', 'value' => '4'],
                ['label' => 'اجمالي الشقق', 'value' => '24'],
            ],
            'filters' => ['بحث باسم العقار', 'فلترة النوع', 'فلترة الحالة'],
            'rows' => [
                ['A-101', '95 م2', 'نموذج A', 'متاح'],
                ['A-102', '120 م2', 'نموذج B', 'محجوز'],
                ['B-201', '95 م2', 'نموذج A', 'متاح'],
                ['C-301', '140 م2', 'نموذج C', 'تم البيع'],
            ],
            'quickActions' => ['إضافة عقار', 'تحديد نماذج المساحات', 'توليد الوحدات تلقائيا'],
            'next' => 'clients',
        ],
        'clients' => [
            'kpis' => [
                ['label' => 'عدد العملاء', 'value' => '18'],
                ['label' => 'عملاء جدد هذا الشهر', 'value' => '5'],
                ['label' => 'عملاء متعاقدون', 'value' => '11'],
            ],
            'filters' => ['بحث بالاسم/الهاتف', 'مصدر العميل', 'حالة التعاقد'],
            'rows' => [
                ['CL-1001', 'محمد السيد', '0100XXXX111', 'متعاقد'],
                ['CL-1002', 'منة الله علي', '0100XXXX222', 'قيد التفاوض'],
                ['CL-1003', 'عبدالله حسن', '0100XXXX333', 'متعاقد'],
            ],
            'quickActions' => ['إضافة عميل', 'تسجيل متابعة', 'تحويل الى عقد'],
            'next' => 'contracts',
        ],
        'contracts' => [
            'kpis' => [
                ['label' => 'العقود النشطة', 'value' => '11'],
                ['label' => 'قيمة العقود', 'value' => '14,200,000 ج.م'],
                ['label' => 'استحقاقات هذا الشهر', 'value' => '420,000 ج.م'],
            ],
            'filters' => ['رقم العقد', 'العميل', 'حالة السداد'],
            'rows' => [
                ['CT-2026-001', 'CL-1001', 'A-102', '1,650,000 ج.م'],
                ['CT-2026-002', 'CL-1003', 'C-301', '2,150,000 ج.م'],
                ['CT-2026-003', 'CL-1007', 'B-204', '1,480,000 ج.م'],
            ],
            'quickActions' => ['إنشاء عقد', 'إضافة ملحق عقد', 'طباعة العقد'],
            'next' => 'sales',
        ],
        'sales' => [
            'kpis' => [
                ['label' => 'المبيعات الكلية', 'value' => '3,800,000 ج.م'],
                ['label' => 'الدفعات المحصلة', 'value' => '1,850,000 ج.م'],
                ['label' => 'المتبقي من المبيعات', 'value' => '1,950,000 ج.م'],
            ],
            'filters' => ['رقم عملية البيع', 'الفترة', 'حالة التحصيل'],
            'rows' => [
                ['SL-001', 'CT-2026-001', '1,650,000 ج.م', '800,000 ج.م'],
                ['SL-002', 'CT-2026-002', '2,150,000 ج.م', '1,050,000 ج.م'],
            ],
            'quickActions' => ['تسجيل بيع', 'جدولة اقساط', 'توليد ايصال مقدم'],
            'next' => 'revenues',
        ],
        'revenues' => [
            'kpis' => [
                ['label' => 'اجمالي الايرادات', 'value' => '2,100,000 ج.م'],
                ['label' => 'ايراد اقساط', 'value' => '1,850,000 ج.م'],
                ['label' => 'ايراد خدمات', 'value' => '250,000 ج.م'],
            ],
            'filters' => ['رقم الايصال', 'نوع الايراد', 'تاريخ التحصيل'],
            'rows' => [
                ['RV-001', 'قسط بيع', '750,000 ج.م', 'SL-001'],
                ['RV-002', 'قسط بيع', '1,100,000 ج.م', 'SL-002'],
                ['RV-003', 'خدمات وصيانة', '250,000 ج.م', 'SP-2026-Q1'],
            ],
            'quickActions' => ['تحصيل دفعة', 'إضافة ايراد غير تشغيلي', 'طباعة ايصال'],
            'next' => 'cashbox',
        ],
        'cashbox' => [
            'kpis' => [
                ['label' => 'رصيد افتتاحي', 'value' => '200,000 ج.م'],
                ['label' => 'مقبوضات', 'value' => '2,100,000 ج.م'],
                ['label' => 'مدفوعات', 'value' => '680,000 ج.م'],
                ['label' => 'الرصيد الحالي', 'value' => '1,620,000 ج.م'],
            ],
            'filters' => ['اليومية', 'نوع الحركة', 'طريقة الدفع'],
            'rows' => [
                ['CB-001', 'قبض', '750,000 ج.م', 'RV-001'],
                ['CB-002', 'قبض', '1,100,000 ج.م', 'RV-002'],
                ['CB-003', 'صرف', '380,000 ج.م', 'EX-001'],
                ['CB-004', 'صرف', '300,000 ج.م', 'EX-002'],
            ],
            'quickActions' => ['حركة قبض', 'حركة صرف', 'قفل يومية الصندوق'],
            'next' => 'expenses',
        ],
        'expenses' => [
            'kpis' => [
                ['label' => 'اجمالي المصروفات', 'value' => '680,000 ج.م'],
                ['label' => 'مصروفات تشغيل', 'value' => '380,000 ج.م'],
                ['label' => 'مصروفات تسويق', 'value' => '300,000 ج.م'],
            ],
            'filters' => ['مركز تكلفة', 'نوع المصروف', 'اعتماد الصرف'],
            'rows' => [
                ['EX-001', 'تشغيل موقع', '380,000 ج.م', 'معتمد'],
                ['EX-002', 'حملة تسويقية', '300,000 ج.م', 'معتمد'],
            ],
            'quickActions' => ['إضافة مصروف', 'رفع مرفق فاتورة', 'طلب اعتماد'],
            'next' => 'debts',
        ],
        'debts' => [
            'kpis' => [
                ['label' => 'اجمالي المديونية', 'value' => '1,950,000 ج.م'],
                ['label' => 'متأخرات حالية', 'value' => '280,000 ج.م'],
                ['label' => 'نسبة التحصيل', 'value' => '48.68%'],
            ],
            'filters' => ['حسب العميل', 'حسب العقد', 'عمر الدين'],
            'rows' => [
                ['DB-001', 'CT-2026-001', '850,000 ج.م', 'متوسط'],
                ['DB-002', 'CT-2026-002', '1,100,000 ج.م', 'منخفض'],
            ],
            'quickActions' => ['إضافة خطة سداد', 'إرسال تذكير تلقائي', 'تجميد مديونية'],
            'next' => 'settlements',
        ],
        'settlements' => [
            'kpis' => [
                ['label' => 'صافي الربح التشغيلي', 'value' => '1,420,000 ج.م'],
                ['label' => 'نسبة التسوية المنجزة', 'value' => '86%'],
                ['label' => 'تسويات معلقة', 'value' => '3'],
            ],
            'filters' => ['الفترة', 'نوع التسوية', 'حالة الاعتماد'],
            'rows' => [
                ['ST-001', 'تسوية ايراد/صندوق', 'مغلق', 'RV-001/RV-002'],
                ['ST-002', 'تسوية مصروفات', 'مغلق', 'EX-001/EX-002'],
                ['ST-003', 'تسوية عهدة', 'معلق', 'ADV-009'],
            ],
            'quickActions' => ['إنشاء تسوية', 'اعتماد قيد', 'تصدير قيود التسوية'],
            'next' => 'reports',
        ],
        'reports' => [
            'kpis' => [
                ['label' => 'الايرادات', 'value' => '2,100,000 ج.م'],
                ['label' => 'المصروفات', 'value' => '680,000 ج.م'],
                ['label' => 'صافي النتيجة', 'value' => '1,420,000 ج.م'],
                ['label' => 'رصيد الصندوق', 'value' => '1,620,000 ج.م'],
            ],
            'filters' => ['تقرير حسب العقار', 'تقرير حسب العميل', 'تقرير زمني'],
            'rows' => [
                ['ملخص الربحية', '2,100,000', '680,000', '1,420,000'],
                ['تدفقات الصندوق', '2,100,000', '680,000', '1,620,000'],
                ['كشف مديونية', '-', '1,950,000', '48.68% تحصيل'],
            ],
            'quickActions' => ['توليد تقرير PDF', 'تصدير Excel', 'مقارنة شهرية'],
            'next' => 'settings',
        ],
        'settings' => [
            'kpis' => [
                ['label' => 'السنة المالية', 'value' => '2026'],
                ['label' => 'العملة الاساسية', 'value' => 'ج.م'],
                ['label' => 'حالة النسخ الاحتياطي', 'value' => 'آخر نسخة: اليوم'],
            ],
            'filters' => ['اعدادات عامة', 'الترقيم', 'الصلاحيات'],
            'rows' => [
                ['تسلسل العقود', 'CT-YYYY-###', 'مفعل', 'system'],
                ['تسلسل الايصالات', 'RV-###', 'مفعل', 'finance'],
                ['تنبيه متأخرات', '7 ايام', 'مفعل', 'collections'],
            ],
            'quickActions' => ['تعديل الاعدادات العامة', 'إدارة القوالب', 'تشغيل نسخة احتياطية'],
            'next' => 'demo',
        ],
        'remaining' => [
            'kpis' => [
                ['label' => 'اجمالي المتبقي', 'value' => '1,950,000 ج.م'],
                ['label' => 'اقساط مستحقة', 'value' => '420,000 ج.م'],
                ['label' => 'اقساط متأخرة', 'value' => '280,000 ج.م'],
            ],
            'filters' => ['حسب العقد', 'حسب تاريخ الاستحقاق', 'حسب حالة العميل'],
            'rows' => [
                ['CT-2026-001', '850,000 ج.م', '160,000 ج.م', 'متابعة'],
                ['CT-2026-002', '1,100,000 ج.م', '120,000 ج.م', 'تذكير'],
            ],
            'quickActions' => ['جدولة تحصيل', 'إرسال إشعار', 'تقرير متبقي'],
            'next' => 'debts',
        ],
    ];

    $moduleViews = [];
    foreach (array_keys($wireflow) as $key) {
        $moduleViews[$key] = 'modules.wireflow';
    }

    $demoStepOrder = [
        'role-permission',
        'shareholders',
        'properties',
        'clients',
        'contracts',
        'sales',
        'revenues',
        'cashbox',
        'expenses',
        'debts',
        'settlements',
        'reports',
        'settings',
    ];

    return view($moduleViews[$module] ?? 'module-placeholder', [
        'title' => $modules[$module]['label'] . ' | Mohaseb Aqary',
        'pageTitle' => $modules[$module]['label'],
        'moduleKey' => $module,
        'module' => $modules[$module],
        'modules' => $modules,
        'moduleData' => $wireflow[$module] ?? null,
        'demoContext' => [
            'project' => 'مشروع النخبة ريزيدنس',
            'period' => '2026 Q1',
            'currency' => 'جنيه مصري',
        ],
        'demoStepOrder' => $demoStepOrder,
    ]);
})->name('modules.show');
