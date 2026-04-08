<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreShareholderRequest;
use App\Http\Requests\UpdateShareholderRequest;
use App\Models\Shareholder;
use App\Services\ShareholderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ShareholderController extends Controller
{
    public function __construct(private readonly ShareholderService $shareholderService)
    {
    }

    public function index(): View
    {
        return view('shareholders.index', [
            'title' => 'المساهمين | Mohaseb Aqary',
            'pageTitle' => 'المساهمين',
            'shareholders' => $this->shareholderService->paginate(10),
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('shareholders.create', [
            'title' => 'إضافة مساهم | Mohaseb Aqary',
            'pageTitle' => 'إضافة مساهم',
            'modules' => $this->modules(),
        ]);
    }

    public function store(StoreShareholderRequest $request): RedirectResponse
    {
        $this->shareholderService->create($request->validated());

        return redirect()->route('shareholders.index')->with('success', 'تم إضافة المساهم بنجاح.');
    }

    public function show(Shareholder $shareholder): View
    {
        return view('shareholders.show', [
            'title' => 'تفاصيل المساهم | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل المساهم',
            'shareholder' => $this->shareholderService->findOrFail((int) $shareholder->id),
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Shareholder $shareholder): View
    {
        return view('shareholders.edit', [
            'title' => 'تعديل المساهم | Mohaseb Aqary',
            'pageTitle' => 'تعديل المساهم',
            'shareholder' => $this->shareholderService->findOrFail((int) $shareholder->id),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdateShareholderRequest $request, Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->update($shareholder, $request->validated());

        return redirect()->route('shareholders.index')->with('success', 'تم تحديث المساهم بنجاح.');
    }

    public function destroy(Shareholder $shareholder): RedirectResponse
    {
        $this->shareholderService->delete($shareholder);

        return redirect()->route('shareholders.index')->with('success', 'تم حذف المساهم بنجاح.');
    }

    private function modules(): array
    {
        return [
            'role-permission' => ['label' => 'Role & Permission', 'icon' => 'fa-user-shield', 'route' => 'modules.show'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
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
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'modules.show'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'modules.show'],
        ];
    }
}
