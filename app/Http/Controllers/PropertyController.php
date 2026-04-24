<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePropertyRequest;
use App\Http\Requests\UpdatePropertyRequest;
use App\Models\Area;
use App\Models\Facing;
use App\Models\Land;
use App\Models\Project;
use App\Models\Property;
use App\Models\Shareholder;
use App\Services\PropertyService;
use App\Support\ListingFilters;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class PropertyController extends Controller
{
    public function __construct(private readonly PropertyService $propertyService) {}

    public function index(Project $project, Request $request): View
    {
        $filters = ListingFilters::fromRequest($request);
        $query = Property::query()->with(['area:id,name', 'land:id,name']);
        if ($filters->q !== '') {
            $like = '%'.$filters->likeTerm().'%';
            $query->where(function ($w) use ($like): void {
                $w->where('name', 'like', $like)
                    ->orWhere('property_type', 'like', $like)
                    ->orWhere('location', 'like', $like)
                    ->orWhere('land_name', 'like', $like)
                    ->orWhereHas('area', fn ($a) => $a->where('name', 'like', $like))
                    ->orWhereHas('land', fn ($l) => $l->where('name', 'like', $like));
            });
        }
        $filters->applyWhereDate($query, 'created_at');

        $forKpis = (clone $query)->get();
        $propertyKpis = [
            'count' => $forKpis->count(),
            'avg_floors' => $forKpis->count() ? round((float) $forKpis->avg('floors_count'), 1) : 0,
            'sum_units' => (float) $forKpis->sum('total_apartments'),
            'type_count' => $forKpis->pluck('property_type')->filter()->unique()->count(),
        ];

        $properties = $query->latest()->paginate(10)->withQueryString();

        return view('properties.index', [
            'title' => 'العقارات | Mohaseb Aqary',
            'pageTitle' => 'العقارات',
            'project' => $project,
            'propertyKpis' => $propertyKpis,
            'properties' => $properties,
            'modules' => $this->modules(),
        ]);
    }

    public function create(): View
    {
        return view('properties.create', [
            'title' => 'إضافة عقار | Mohaseb Aqary',
            'pageTitle' => 'إضافة عقار',
            'property' => new Property,
            'areas' => Area::query()->select('id', 'name')->orderBy('name')->get(),
            'lands' => $this->landsSelectableForProperty(new Property),
            'shareholders' => Shareholder::query()->select('id', 'name', 'share_percentage')->orderBy('name')->get(),
            'facings' => Facing::query()->orderBy('sort_order')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        $this->propertyService->create($request->validated());

        return redirect()->route('properties.index')->with('success', 'تم إضافة العقار بنجاح.');
    }

    public function show(Project $project, Property $property): View
    {
        return view('properties.show', [
            'title' => 'تفاصيل العقار | Mohaseb Aqary',
            'pageTitle' => 'تفاصيل العقار',
            'property' => $this->propertyService->findOrFail((int) $property->id),
            'facingNames' => Facing::query()->orderBy('sort_order')->pluck('name', 'code'),
            'modules' => $this->modules(),
        ]);
    }

    public function edit(Project $project, Property $property): View
    {
        return view('properties.edit', [
            'title' => 'تعديل العقار | Mohaseb Aqary',
            'pageTitle' => 'تعديل العقار',
            'property' => $this->propertyService->findOrFail((int) $property->id),
            'areas' => Area::query()->select('id', 'name')->orderBy('name')->get(),
            'lands' => $this->landsSelectableForProperty($property),
            'shareholders' => Shareholder::query()->select('id', 'name', 'share_percentage')->orderBy('name')->get(),
            'facings' => Facing::query()->orderBy('sort_order')->orderBy('name')->get(),
            'modules' => $this->modules(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Project $project, Property $property): RedirectResponse
    {
        $this->propertyService->update($property, $request->validated());

        return redirect()->route('properties.index')->with('success', 'تم تحديث العقار بنجاح.');
    }

    public function destroy(Project $project, Property $property): RedirectResponse
    {
        $this->propertyService->delete($property);

        return redirect()->route('properties.index')->with('success', 'تم حذف العقار بنجاح.');
    }

    /**
     * أراضٍ غير مربوطة بعقار آخر في المشروع (العقار الحالي يُستثنى عند التعديل).
     *
     * @return Collection<int, Land>
     */
    private function landsSelectableForProperty(Property $property): Collection
    {
        $usedLandIds = Property::query()
            ->whereNotNull('land_id')
            ->when($property->exists, static fn ($q) => $q->where('id', '!=', $property->id))
            ->pluck('land_id');

        return Land::query()
            ->select([
                'id',
                'name',
                'area_id',
                'land_cost',
                'building_license_cost',
                'piles_cost',
                'excavation_cost',
                'gravel_cost',
                'sand_cost',
                'cement_cost',
                'steel_cost',
                'carpentry_labor_cost',
                'blacksmith_labor_cost',
                'mason_labor_cost',
                'electrician_labor_cost',
                'tips_cost',
            ])
            ->when($usedLandIds->isNotEmpty(), static fn ($q) => $q->whereNotIn('id', $usedLandIds))
            ->orderBy('name')
            ->get();
    }

    private function modules(): array
    {
        return [
            'projects' => ['label' => 'المشاريع', 'icon' => 'fa-diagram-project', 'route' => 'projects.index'],
            'areas' => ['label' => 'المناطق', 'icon' => 'fa-location-dot', 'route' => 'areas.index'],
            'facings' => ['label' => 'الوجهات', 'icon' => 'fa-compass-drafting', 'route' => 'facings.index'],
            'lands' => ['label' => 'الأراضي', 'icon' => 'fa-map-location-dot', 'route' => 'lands.index'],
            'properties' => ['label' => 'عقارات', 'icon' => 'fa-building', 'route' => 'properties.index'],
            'clients' => ['label' => 'عملاء', 'icon' => 'fa-users', 'route' => 'clients.index'],
            'contracts' => ['label' => 'العقود', 'icon' => 'fa-file-signature', 'route' => 'contracts.index'],
            'sales' => ['label' => 'المبيعات', 'icon' => 'fa-cart-shopping', 'route' => 'sales.index'],
            'revenues' => ['label' => 'ايرادات', 'icon' => 'fa-money-bill-trend-up', 'route' => 'revenues.index'],
            'expenses' => ['label' => 'المصروفات', 'icon' => 'fa-money-bill-wave', 'route' => 'expenses.index'],
            'cashbox' => ['label' => 'الصندوق', 'icon' => 'fa-vault', 'route' => 'cashbox.index'],
            'remaining' => ['label' => 'المتبقي', 'icon' => 'fa-hourglass-half', 'route' => 'remaining.index'],
            'debts' => ['label' => 'المديونيه', 'icon' => 'fa-hand-holding-dollar', 'route' => 'debts.index'],
            'settlements' => ['label' => 'تصفيات', 'icon' => 'fa-filter-circle-dollar', 'route' => 'settlements.index'],
            'shareholders' => ['label' => 'المساهمين', 'icon' => 'fa-people-group', 'route' => 'shareholders.index'],
            'reports' => ['label' => 'التقارير', 'icon' => 'fa-chart-line', 'route' => 'reports.index'],
            'settings' => ['label' => 'الاعدادات', 'icon' => 'fa-gear', 'route' => 'settings.edit'],
        ];
    }
}
