<?php

/**
 * ربط أسماء المسارات بصلاحيات slug (يجب أن تطابق سجلات permissions بعد التشغيل).
 */
return [
    'home' => 'dashboard.view',

    'projects.index' => 'projects.view',
    'projects.store' => 'projects.manage',
    'projects.edit' => 'projects.manage',
    'projects.update' => 'projects.manage',
    'projects.destroy' => 'projects.manage',
    'projects.draft' => 'projects.manage',
    'projects.restore' => 'projects.manage',

    'dashboard' => 'dashboard.view',

    'properties.index' => 'properties.view',
    'properties.show' => 'properties.view',
    'properties.create' => 'properties.manage',
    'properties.store' => 'properties.manage',
    'properties.edit' => 'properties.manage',
    'properties.update' => 'properties.manage',
    'properties.destroy' => 'properties.manage',

    'areas.index' => 'areas.manage',
    'areas.create' => 'areas.manage',
    'areas.store' => 'areas.manage',
    'areas.edit' => 'areas.manage',
    'areas.update' => 'areas.manage',
    'areas.destroy' => 'areas.manage',

    'facings.index' => 'facings.manage',
    'facings.create' => 'facings.manage',
    'facings.store' => 'facings.manage',
    'facings.edit' => 'facings.manage',
    'facings.update' => 'facings.manage',
    'facings.destroy' => 'facings.manage',

    'lands.index' => 'lands.manage',
    'lands.create' => 'lands.manage',
    'lands.store' => 'lands.manage',
    'lands.edit' => 'lands.manage',
    'lands.update' => 'lands.manage',
    'lands.destroy' => 'lands.manage',

    'shareholders.index' => 'shareholders.view',
    'shareholders.show' => 'shareholders.view',
    'shareholders.create' => 'shareholders.manage',
    'shareholders.store' => 'shareholders.manage',
    'shareholders.edit' => 'shareholders.manage',
    'shareholders.update' => 'shareholders.manage',
    'shareholders.destroy' => 'shareholders.manage',

    'sales.index' => 'sales.view',
    'sales.show' => 'sales.view',
    'sales.create' => 'sales.manage',
    'sales.store' => 'sales.manage',
    'sales.edit' => 'sales.manage',
    'sales.update' => 'sales.manage',
    'sales.destroy' => 'sales.manage',

    'clients.index' => 'clients.view',
    'clients.show' => 'clients.view',

    'contracts.index' => 'contracts.view',
    'contracts.show' => 'contracts.view',

    'revenues.index' => 'revenues.view',
    'revenues.show' => 'revenues.view',
    'revenues.create' => 'revenues.manage',
    'revenues.store' => 'revenues.manage',
    'revenues.edit' => 'revenues.manage',
    'revenues.update' => 'revenues.manage',
    'revenues.destroy' => 'revenues.manage',

    'expenses.index' => 'expenses.view',
    'expenses.create' => 'expenses.manage',
    'expenses.store' => 'expenses.manage',
    'expenses.destroy' => 'expenses.manage',

    'cashbox.index' => 'cashbox.view',
    'cashbox.store' => 'cashbox.manage',

    'debts.index' => 'debts.view',
    'debts.create' => 'debts.manage',
    'debts.store' => 'debts.manage',
    'debts.edit' => 'debts.manage',
    'debts.update' => 'debts.manage',
    'debts.destroy' => 'debts.manage',
    'debts.pay-from-cashbox' => 'debts.manage',

    'remaining.index' => 'remaining.view',

    'settlements.index' => 'settlements.view',

    'reports.index' => 'reports.view',
    'reports.export' => 'reports.export',

    'settings.edit' => 'settings.manage',
    'settings.update' => 'settings.manage',

    'users.index' => 'users.view',
    'users.create' => 'users.manage',
    'users.store' => 'users.manage',
    'users.edit' => 'users.manage',
    'users.update' => 'users.manage',
    'users.destroy' => 'users.manage',
];
