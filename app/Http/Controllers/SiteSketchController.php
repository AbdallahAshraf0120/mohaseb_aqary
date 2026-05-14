<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\Property;
use App\Models\PropertySketchCell;
use App\Models\Sale;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiteSketchController extends Controller
{
    /**
     * صفحة فهرس: قائمة العقارات لكل مشروع لاختيار العقار المراد عرضه ككروكي.
     */
    public function index(Request $request): View
    {
        $projects = Project::query()
            ->listed()
            ->with(['properties' => function ($q): void {
                $q->orderBy('name');
            }])
            ->orderBy('name')
            ->get();

        $selectedId = (int) $request->query('property_id', 0);
        $selectedProperty = $selectedId > 0
            ? Property::query()->with('project')->find($selectedId)
            : null;

        $sketch = $selectedProperty
            ? $this->buildSketch($selectedProperty)
            : null;

        return view('site-sketch.index', [
            'title' => 'مخطط الموقع | Mohaseb Aqary',
            'pageTitle' => 'مخطط الموقع (كروكي العقارات)',
            'projects' => $projects,
            'selectedProperty' => $selectedProperty,
            'sketch' => $sketch,
            'statusOptions' => PropertySketchCell::STATUSES,
        ]);
    }

    /**
     * حفظ حالة خلية واحدة (AJAX).
     */
    public function updateCell(Request $request, Property $property): JsonResponse
    {
        $data = $request->validate([
            'cell_key' => ['required', 'string', 'max:64'],
            'status' => ['required', 'string', Rule::in(array_keys(PropertySketchCell::STATUSES))],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        $cell = PropertySketchCell::query()->updateOrCreate(
            [
                'property_id' => $property->id,
                'cell_key' => $data['cell_key'],
            ],
            [
                'status' => $data['status'],
                'note' => $data['note'] ?? null,
                'updated_by' => (int) ($request->user()?->id ?? 0) ?: null,
            ],
        );

        return response()->json([
            'ok' => true,
            'cell' => [
                'cell_key' => $cell->cell_key,
                'status' => $cell->status,
                'note' => $cell->note,
                'label' => PropertySketchCell::STATUSES[$cell->status] ?? $cell->status,
            ],
        ]);
    }

    /**
     * إعادة الكروكي بالكامل إلى الحالة المحسوبة من البيانات الفعلية (مسح كل التعديلات اليدوية).
     */
    public function reset(Property $property): RedirectResponse
    {
        PropertySketchCell::query()->where('property_id', $property->id)->delete();

        return redirect()->route('site-sketch.index', ['property_id' => $property->id])
            ->with('success', 'تمت إعادة الكروكي إلى الحالة الأصلية.');
    }

    /**
     * بناء الكروكي للعقار: قائمة أدوار، لكل دور قائمة خلايا، مع الحالة المحسوبة والحالة اليدوية إن وُجدت.
     *
     * @return array{floors: list<array<string, mixed>>, legend: array<string, string>, totals: array<string, int>}
     */
    private function buildSketch(Property $property): array
    {
        $apartmentsPerFloor = max(0, (int) ($property->apartments_per_floor ?? 0));
        $totalFloors = max(0, (int) ($property->building_total_floors ?? $property->floors_count ?? 0));
        $registered = collect($property->registered_floors ?? [])
            ->map(fn ($n) => (int) $n)
            ->filter(fn (int $n) => $n >= 1)
            ->unique()->values()->all();
        $mushaa = collect($property->mushaa_floors ?? [])
            ->map(fn ($n) => (int) $n)
            ->filter(fn (int $n) => $n >= 1)
            ->unique()->values()->all();
        $mezzanineFloors = collect($property->mezzanine_floors ?? [])
            ->filter(fn ($row) => is_array($row) && (int) ($row['floor_number'] ?? 0) >= 1)
            ->values()
            ->all();
        $groundShops = max(0, (int) ($property->ground_floor_shops_count ?? 0));

        $manual = PropertySketchCell::query()
            ->where('property_id', $property->id)
            ->get()
            ->keyBy('cell_key');

        // عدد المبيعات (المعتمدة والمعلقة) لكل دور لتلوين الخلايا بحسب الواقع.
        $salesByFloor = Sale::query()
            ->where('property_id', $property->id)
            ->select(['id', 'floor_number', 'approval_status', 'is_mezzanine'])
            ->orderBy('id')
            ->get()
            ->groupBy(fn (Sale $s) => ($s->is_mezzanine ? 'mezz-' : 'floor-').(int) $s->floor_number);

        $floors = [];

        // الأدوار العادية من الأعلى للأسفل
        for ($f = $totalFloors; $f >= 1; $f--) {
            $isRegistered = in_array($f, $registered, true);
            $isMushaa = in_array($f, $mushaa, true);
            $floorKeyPrefix = 'floor-'.$f;

            $cells = [];
            $floorSales = collect($salesByFloor->get($floorKeyPrefix) ?? []);
            $approvedQueue = $floorSales->where('approval_status', 'approved')->values();
            $pendingQueue = $floorSales->where('approval_status', 'pending')->values();

            for ($slot = 1; $slot <= $apartmentsPerFloor; $slot++) {
                $key = $floorKeyPrefix.':slot-'.$slot;
                $computed = 'available';
                $computedLabel = 'متاح';
                if ($approvedQueue->isNotEmpty()) {
                    $approvedQueue->shift();
                    $computed = 'sold';
                    $computedLabel = 'مباع';
                } elseif ($pendingQueue->isNotEmpty()) {
                    $pendingQueue->shift();
                    $computed = 'pending';
                    $computedLabel = 'تحت الاعتماد';
                }
                $cells[] = $this->makeCell($key, 'شقة '.$slot, $computed, $computedLabel, $manual);
            }

            $floors[] = [
                'label' => 'الدور '.$f,
                'sub' => $isRegistered ? 'دور مسجّل' : ($isMushaa ? 'دور مشاع' : null),
                'cells' => $cells,
            ];
        }

        // أدوار الميزانين (لكل دور ميزانين عدد شقق مخصّص في mezzanine_floors)
        foreach ($mezzanineFloors as $mez) {
            $fn = (int) ($mez['floor_number'] ?? 0);
            $cnt = max(0, (int) ($mez['apartments_count'] ?? 0));
            if ($fn < 1 || $cnt < 1) {
                continue;
            }
            $cells = [];
            $floorSales = collect($salesByFloor->get('mezz-'.$fn) ?? []);
            $approvedQueue = $floorSales->where('approval_status', 'approved')->values();
            $pendingQueue = $floorSales->where('approval_status', 'pending')->values();
            for ($slot = 1; $slot <= $cnt; $slot++) {
                $key = 'mezz-'.$fn.':slot-'.$slot;
                $computed = 'available';
                $computedLabel = 'متاح';
                if ($approvedQueue->isNotEmpty()) {
                    $approvedQueue->shift();
                    $computed = 'sold';
                    $computedLabel = 'مباع';
                } elseif ($pendingQueue->isNotEmpty()) {
                    $pendingQueue->shift();
                    $computed = 'pending';
                    $computedLabel = 'تحت الاعتماد';
                }
                $cells[] = $this->makeCell($key, 'شقة '.$slot, $computed, $computedLabel, $manual);
            }
            $floors[] = [
                'label' => 'ميزان دور '.$fn,
                'sub' => 'ميزانين',
                'cells' => $cells,
            ];
        }

        // الدور الأرضي (محلات)
        if ($groundShops > 0) {
            $cells = [];
            for ($i = 1; $i <= $groundShops; $i++) {
                $key = 'ground:shop-'.$i;
                $cells[] = $this->makeCell($key, 'محل '.$i, 'available', 'متاح', $manual);
            }
            $floors[] = [
                'label' => 'الدور الأرضي',
                'sub' => 'محلات',
                'cells' => $cells,
            ];
        }

        $allCells = collect($floors)->flatMap(fn (array $f) => $f['cells']);
        $totals = [
            'total' => $allCells->count(),
            'available' => $allCells->where('effective_status', 'available')->count(),
            'sold' => $allCells->where('effective_status', 'sold')->count(),
            'pending' => $allCells->where('effective_status', 'pending')->count(),
            'reserved' => $allCells->where('effective_status', 'reserved')->count(),
            'viewing' => $allCells->where('effective_status', 'viewing')->count(),
            'blocked' => $allCells->where('effective_status', 'blocked')->count(),
        ];

        return [
            'floors' => $floors,
            'legend' => PropertySketchCell::STATUSES,
            'totals' => $totals,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCell(string $key, string $label, string $computed, string $computedLabel, \Illuminate\Support\Collection $manual): array
    {
        $override = $manual->get($key);
        $effective = $override?->status ?? $computed;
        $effectiveLabel = $override
            ? (PropertySketchCell::STATUSES[$override->status] ?? $override->status)
            : $computedLabel;

        return [
            'key' => $key,
            'label' => $label,
            'computed_status' => $computed,
            'computed_label' => $computedLabel,
            'manual_status' => $override?->status,
            'note' => $override?->note,
            'effective_status' => $effective,
            'effective_label' => $effectiveLabel,
        ];
    }
}
