<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Property;
use App\Models\Shareholder;
use App\Services\PropertyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class PropertyController extends Controller
{
    public function __construct(private readonly PropertyService $propertyService)
    {
    }

    public function index(): View
    {
        $properties = $this->propertyService->paginate(10);

        return view('properties.index', [
            'title' => 'العقارات | Mohaseb Aqary',
            'pageTitle' => 'العقارات',
            'properties' => $properties,
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('properties.create', [
            'title' => 'إضافة عقار | Mohaseb Aqary',
            'pageTitle' => 'إضافة عقار',
            'shareholders' => Shareholder::query()->select('id', 'name')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->propertyService->create($request->validated());

        return redirect()->route('properties.index')->with('success', 'تم إضافة العقار بنجاح.');
    }

    public function show(Property $property): View
    {
        return view('properties.show', [
            'title' => 'تفاصيل العقار | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل العقار',
            'property' => $this->propertyService->findOrFail((int) $property->id),
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Property $property): View
    {
        return view('properties.edit', [
            'title' => 'تعديل العقار | Mohaseb Aqary',
            'pageTitle' => 'تعديل العقار',
            'property' => $this->propertyService->findOrFail((int) $property->id),
            'shareholders' => Shareholder::query()->select('id', 'name')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        $this->propertyService->update($property, $request->validated());

        return redirect()->route('properties.index')->with('success', 'تم تحديث العقار بنجاح.');
    }

    public function destroy(Property $property): RedirectResponse
    {
        $this->propertyService->delete($property);

        return redirect()->route('properties.index')->with('success', 'تم حذف العقار بنجاح.');
    }

    private function modules(): array
    {
        return [
            'role-permission' => ['label' => 'Role & Permission', 'icon' => 'fa-user-shield', 'route' => 'modules.show'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'modules.show'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'modules.show'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'modules.show'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'modules.show'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'modules.show'],
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'modules.show'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'modules.show'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'modules.show'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'modules.show'],
        ];
    }
}
