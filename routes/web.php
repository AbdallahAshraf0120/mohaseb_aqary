<?php

use App\Http\Controllers\PropertyController;
use Illuminate\Support\Facades\Route;

$modules = [
    'role-permission' => ['label' => 'Role & Permission', 'icon' => 'fa-user-shield', 'route' => 'modules.show'],
    'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
    'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'modules.show'],
    'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'modules.show'],
    'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'modules.show'],
    'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'modules.show'],
    'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'modules.show'],
    'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'modules.show'],
    'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'modules.show'],
    'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'modules.show'],
    'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'modules.show'],
    'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'modules.show'],
    'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'modules.show'],
    'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'modules.show'],
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

    return view('module-placeholder', [
        'title' => $modules[$module]['label'] . ' | Mohaseb Aqary',
        'pageTitle' => $modules[$module]['label'],
        'moduleKey' => $module,
        'module' => $modules[$module],
        'modules' => $modules,
    ]);
})->name('modules.show');
