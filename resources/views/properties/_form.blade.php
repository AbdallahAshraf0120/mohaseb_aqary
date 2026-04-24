@csrf

@php
    $shareholderAllocations = collect(old('shareholder_percentages', []));

    if ($shareholderAllocations->isEmpty() && isset($property)) {
        $shareholderAllocations = collect($property->shareholder_allocations ?? [])
            ->mapWithKeys(fn ($item) => [(string) ($item['shareholder_id'] ?? '') => $item['percentage'] ?? '']);
    }

    if ($shareholderAllocations->isEmpty()) {
        $shareholderAllocations = collect($shareholders ?? [])
            ->mapWithKeys(fn ($shareholder) => [
                (string) $shareholder->id => (float) ($shareholder->share_percentage ?? 0),
            ]);
    }

    $facingsList = $facings ?? collect();
    $defaultFacingCode = $facingsList->first()->code ?? 'normal';

    $apartmentModels = old('apartment_models', $property->apartment_models ?? [[
        'model_name' => '',
        'area' => '',
        'rooms_count' => '',
        'bathrooms_count' => '',
        'view_type' => $defaultFacingCode,
    ]]);
    $hasMezzanine = (bool) old('has_mezzanine', isset($property) ? $property->has_mezzanine : false);
    $buildingTotalFloors = (int) old('building_total_floors', $property->building_total_floors ?? $property->floors_count ?? 1);
    $registeredFloors = collect(old('registered_floors', $property->registered_floors ?? []))
        ->map(fn ($value) => (int) $value)
        ->filter(fn (int $value) => $value >= 1)
        ->unique()
        ->sort()
        ->values()
        ->all();
    $mezzanineFloors = old('mezzanine_floors', $property->mezzanine_floors ?? []);
    if (empty($mezzanineFloors) && $hasMezzanine && (int) ($property->mezzanine_apartments_count ?? 0) > 0) {
        $mezzanineFloors = [[
            'floor_number' => 1,
            'apartments_count' => (int) $property->mezzanine_apartments_count,
        ]];
    }
    $mushaaFloors = collect(old('mushaa_floors', []))
        ->map(fn ($value) => (int) $value)
        ->filter(fn (int $value) => $value >= 1)
        ->unique()
        ->sort()
        ->values()
        ->all();
    if ($mushaaFloors === [] && isset($property)) {
        $mushaaFloors = collect($property->mushaa_floors ?? [])
            ->map(fn ($value) => (int) $value)
            ->filter(fn (int $value) => $value >= 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
    if ($mushaaFloors === [] && isset($property)) {
        $mushaaFloors = collect($property->mezzanine_floors ?? [])
            ->filter(fn ($row) => is_array($row) && !empty($row['is_mushaa']))
            ->map(fn ($row) => (int) ($row['floor_number'] ?? 0))
            ->filter(fn (int $value) => $value >= 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }
    $landsData = collect($lands ?? [])
        ->mapWithKeys(fn ($land) => [
            (string) $land->id => [
                'id' => (int) $land->id,
                'name' => (string) $land->name,
                'area_id' => $land->area_id ? (int) $land->area_id : null,
                'land_cost' => (float) ($land->land_cost ?? 0),
                'building_license_cost' => (float) ($land->building_license_cost ?? 0),
                'piles_cost' => (float) ($land->piles_cost ?? 0),
                'excavation_cost' => (float) ($land->excavation_cost ?? 0),
                'gravel_cost' => (float) ($land->gravel_cost ?? 0),
                'sand_cost' => (float) ($land->sand_cost ?? 0),
                'cement_cost' => (float) ($land->cement_cost ?? 0),
                'steel_cost' => (float) ($land->steel_cost ?? 0),
                'carpentry_labor_cost' => (float) ($land->carpentry_labor_cost ?? 0),
                'blacksmith_labor_cost' => (float) ($land->blacksmith_labor_cost ?? 0),
                'mason_labor_cost' => (float) ($land->mason_labor_cost ?? 0),
                'electrician_labor_cost' => (float) ($land->electrician_labor_cost ?? 0),
                'tips_cost' => (float) ($land->tips_cost ?? 0),
            ],
        ])
        ->all();
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم العقار</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $property->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">نوع العقار</label>
        <input type="text" name="property_type" class="form-control" value="{{ old('property_type', $property->property_type ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>الأرض</span>
            <a href="{{ route('lands.create', $project) }}" class="small text-decoration-none">+ إضافة أرض جديدة</a>
        </label>
        <select name="land_id" id="land_id" class="form-select">
            <option value="">اختر الأرض</option>
            @foreach ($lands ?? [] as $land)
                <option value="{{ $land->id }}" @selected((string) old('land_id', $property->land_id ?? '') === (string) $land->id)>{{ $land->name }}</option>
            @endforeach
        </select>
        <p class="form-text small text-muted mb-0">كل أرض يمكن ربطها بعقار واحد فقط ضمن نفس المشروع؛ الأراضي المستخدمة لا تظهر في القائمة.</p>
    </div>
    <div class="col-md-6">
        <label class="form-label">اسم الأرض (اختياري)</label>
        <input type="text" name="land_name" id="land_name" class="form-control" value="{{ old('land_name', $property->land_name ?? '') }}" placeholder="مثال: قطعة 17 - امتداد النخيل">
    </div>
    <div class="col-md-6">
        <label class="form-label">المنطقة</label>
        <select name="area_id" class="form-select" required>
            <option value="">اختر المنطقة</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected((string) old('area_id', $property->area_id ?? '') === (string) $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <div class="card border-secondary-subtle shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header py-2 bg-body-secondary border-0"><strong>بيانات الأرض ومصاريف البناء</strong></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">تكلفة الأرض</label>
                        <input type="number" step="0.01" min="0" name="land_cost" id="land_cost" class="form-control"
                               value="{{ old('land_cost', $property->land_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رخصة البناء</label>
                        <input type="number" step="0.01" min="0" name="building_license_cost" id="building_license_cost" class="form-control"
                               value="{{ old('building_license_cost', $property->building_license_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">خوازيق</label>
                        <input type="number" step="0.01" min="0" name="piles_cost" id="piles_cost" class="form-control"
                               value="{{ old('piles_cost', $property->piles_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">حفر</label>
                        <input type="number" step="0.01" min="0" name="excavation_cost" id="excavation_cost" class="form-control"
                               value="{{ old('excavation_cost', $property->excavation_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">ظلط</label>
                        <input type="number" step="0.01" min="0" name="gravel_cost" id="gravel_cost" class="form-control"
                               value="{{ old('gravel_cost', $property->gravel_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">رملة</label>
                        <input type="number" step="0.01" min="0" name="sand_cost" id="sand_cost" class="form-control"
                               value="{{ old('sand_cost', $property->sand_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">أسمنت</label>
                        <input type="number" step="0.01" min="0" name="cement_cost" id="cement_cost" class="form-control"
                               value="{{ old('cement_cost', $property->cement_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">حديد</label>
                        <input type="number" step="0.01" min="0" name="steel_cost" id="steel_cost" class="form-control"
                               value="{{ old('steel_cost', $property->steel_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عمالة نجارة</label>
                        <input type="number" step="0.01" min="0" name="carpentry_labor_cost" id="carpentry_labor_cost" class="form-control"
                               value="{{ old('carpentry_labor_cost', $property->carpentry_labor_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عمالة حدادة</label>
                        <input type="number" step="0.01" min="0" name="blacksmith_labor_cost" id="blacksmith_labor_cost" class="form-control"
                               value="{{ old('blacksmith_labor_cost', $property->blacksmith_labor_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عمالة بناَّء</label>
                        <input type="number" step="0.01" min="0" name="mason_labor_cost" id="mason_labor_cost" class="form-control"
                               value="{{ old('mason_labor_cost', $property->mason_labor_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">عمالة كهربائي</label>
                        <input type="number" step="0.01" min="0" name="electrician_labor_cost" id="electrician_labor_cost" class="form-control"
                               value="{{ old('electrician_labor_cost', $property->electrician_labor_cost ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">إكراميات</label>
                        <input type="number" step="0.01" min="0" name="tips_cost" id="tips_cost" class="form-control"
                               value="{{ old('tips_cost', $property->tips_cost ?? 0) }}">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <label class="form-label">إجمالي أدوار البرج</label>
        <input type="number" min="1" name="building_total_floors" id="building_total_floors" class="form-control"
               value="{{ $buildingTotalFloors }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">عدد الأدوار المسجلة</label>
        <input type="number" min="1" name="floors_count" id="floors_count" class="form-control"
               value="{{ old('floors_count', $property->floors_count ?? 1) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">عدد الشقق بكل دور متكرر</label>
        <input type="number" min="1" name="apartments_per_floor" id="apartments_per_floor" class="form-control"
               value="{{ old('apartments_per_floor', $property->apartments_per_floor ?? 1) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">عدد محلات الأرضي (دور 0)</label>
        <input type="number" min="0" name="ground_floor_shops_count" id="ground_floor_shops_count" class="form-control"
               value="{{ old('ground_floor_shops_count', $property->ground_floor_shops_count ?? 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>إجمالي الشقق بالعقار</span>
            <button type="button" class="btn btn-link btn-sm p-0" id="recalculate-total">إعادة الحساب</button>
        </label>
        <input type="number" min="1" name="total_apartments" id="total_apartments" class="form-control"
               value="{{ old('total_apartments', $property->total_apartments ?? 1) }}" required>
        <p class="form-text small text-muted mb-0">
            يُحسب تلقائيًا: (الأدوار المختارة × الشقق لكل دور) + شقق الميزان (صفوف كاملة برقم دور) + محلات الأرضي.
            <strong>أدوار مشاعة:</strong> لا تُنقص ولا تُزيد هذا الرقم — الوحدات على تلك الأدوار ما زالت تُحسب ضمن الإجمالي؛ «المشاع» يخص <strong>تقسيم العائد أو الحصص</strong> بين المساهمين والشريك وليس حذف شقق من العدد.
        </p>
    </div>

    <div class="col-12">
        <div class="card border-secondary-subtle shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header py-2 bg-body-secondary border-0"><strong>اختيار الأدوار المسجلة بالعقار</strong></div>
            <div class="card-body">
                <p class="text-muted small mb-2">حدد الأدوار التي تملكها/ستسجلها (مثال: من 12 دور تختار 6 أدوار فقط). إذا كان لنفس الرقم ميزان في الجدول أدناه، يُعرض هنا <strong>دور … (سكني)</strong> لتمييزه عن <strong>دور … (ميزان)</strong>؛ وإلا يظهر رقم الدور فقط (مثل الدور الأول العادي).</p>
                <div class="d-flex flex-wrap gap-2" id="registered-floors-box"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-secondary-subtle shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header py-2 bg-body-secondary border-0"><strong>أدوار مشاعة في العقار</strong></div>
            <div class="card-body">
                <p class="text-muted small mb-2">
                    حدّد أي دور من أدوار البرج (1 … إجمالي الأدوار) يكون <strong>مشاعًا</strong>، سواء كان ضمن الأدوار المسجلة أو ضمن الميزان.
                    هذا <strong>لا يغيّر «إجمالي الشقق بالعقار»</strong>: الشقق على الأدوار المشاعة تبقى وحدات فعلية ضمن المجموع؛ يتغيّر فقط <strong>من يستحق كم من عائد</strong> تلك الوحدات عند وجود شريك.
                    <strong>عند إدخال اسم الشريك أدناه:</strong> يُفترض أن يكون عائد وحدات ذلك الدور (مثلاً من البيع أو التحصيل)
                    <strong>بالنصف</strong> — <strong>50٪</strong> لمجموعة المساهمين (ويُوزَّع نصف المساهمين هذا بينهم بنسب الحقول في «نسبة كل مساهم في العقار»)،
                    و<strong>50٪</strong> لصالح الشريك المسمّى.
                    <strong>بدون اسم شريك:</strong> لا يُطبَّق تقسيم 50/50 مع شريك خارجي؛ يُنصح بتعبئة الاسم عند وجود شراكة فعلية على تلك الأدوار.
                </p>
                <div class="d-flex flex-wrap gap-2 mb-3" id="mushaa-floors-box"></div>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="mushaa_partner_name">اسم الشريك الآخر</label>
                        <input type="text" name="mushaa_partner_name" id="mushaa_partner_name" class="form-control"
                               value="{{ old('mushaa_partner_name', isset($property) ? ($property->mushaa_partner_name ?? '') : '') }}"
                               placeholder="مثال: شريك خارجي / شركة أخرى">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-secondary-subtle shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center py-2 bg-body-secondary border-0">
                <strong>أدوار الميزان (أكثر من ميزان)</strong>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-mezzanine-row">إضافة ميزان</button>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-2">أدخل رقم الدور الذي يعلوه/يرتبط به الميزان (مثال: 1 لميزان فوق الدور الأول السكني). في البيع سيظهر للمستخدم خيار <strong>الدور … (سكني)</strong> و<strong>الدور … (ميزان)</strong> عندما ينطبق ذلك.</p>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>رقم الدور</th>
                            <th>عدد الشقق بالميزان</th>
                            <th class="text-end">حذف</th>
                        </tr>
                        </thead>
                        <tbody id="mezzanine-floors-body">
                        @forelse($mezzanineFloors as $i => $mezz)
                            <tr>
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[{{ $i }}][floor_number]" value="{{ $mezz['floor_number'] ?? '' }}" placeholder="مثال: 12"></td>
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[{{ $i }}][apartments_count]" value="{{ $mezz['apartments_count'] ?? '' }}" placeholder="مثال: 2"></td>
                                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-mezzanine-row">حذف</button></td>
                            </tr>
                        @empty
                            <tr>
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[0][floor_number]" placeholder="مثال: 12"></td>
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[0][apartments_count]" placeholder="مثال: 2"></td>
                                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-mezzanine-row">حذف</button></td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-secondary-subtle shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header py-2 bg-body-secondary border-0"><strong>نسبة كل مساهم في العقار</strong></div>
            <div class="card-body">
                <p class="small text-muted border rounded px-2 py-2 bg-body-secondary mb-3">
                    النسب هنا تخص <strong>حصة المساهمين</strong> من العقار. للأدوار المشاعة <strong>مع شريك مذكور</strong>،
                    يُوزَّع <strong>نصف</strong> عائد وحدات ذلك الدور بين المساهمين وفق هذه النسب (من أصل 100٪ لحصة المساهمين في ذلك النصف)،
                    والنصف الآخر للشريك.
                </p>
                <div class="row g-3">
                    @foreach ($shareholders as $shareholder)
                        <div class="col-md-4">
                            <label class="form-label">{{ $shareholder->name }} (%)</label>
                            <input type="number" step="0.01" min="0" max="100"
                                   name="shareholder_percentages[{{ $shareholder->id }}]"
                                   class="form-control"
                                   value="{{ $shareholderAllocations->get((string) $shareholder->id, '') }}">
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border-secondary-subtle shadow-sm rounded-3 mb-3 overflow-hidden">
            <div class="card-header d-flex justify-content-between align-items-center py-2 flex-wrap gap-2 bg-body-secondary border-0">
                <strong>نماذج الشقق (المساحة = نموذج)</strong>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('facings.index') }}" class="btn btn-outline-secondary btn-sm">إدارة الوجهات</a>
                    <button type="button" class="btn btn-outline-primary btn-sm" id="add-model-row">إضافة نموذج</button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead>
                        <tr>
                            <th>اسم النموذج</th>
                            <th>المساحة (م2)</th>
                            <th>الغرف</th>
                            <th>الحمامات</th>
                            <th>الواجهة</th>
                            <th class="text-end">حذف</th>
                        </tr>
                        </thead>
                        <tbody id="apartment-models-body">
                        @foreach ($apartmentModels as $i => $model)
                            <tr>
                                <td>
                                    <input type="text" class="form-control" name="apartment_models[{{ $i }}][model_name]"
                                           value="{{ $model['model_name'] ?? '' }}" placeholder="مثال: نموذج A">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="1" class="form-control"
                                           name="apartment_models[{{ $i }}][area]" value="{{ $model['area'] ?? '' }}"
                                           placeholder="مثال: 120">
                                </td>
                                <td>
                                    <input type="number" min="0" class="form-control"
                                           name="apartment_models[{{ $i }}][rooms_count]" value="{{ $model['rooms_count'] ?? '' }}"
                                           placeholder="مثال: 3">
                                </td>
                                <td>
                                    <input type="number" min="0" class="form-control"
                                           name="apartment_models[{{ $i }}][bathrooms_count]" value="{{ $model['bathrooms_count'] ?? '' }}"
                                           placeholder="مثال: 2">
                                </td>
                                <td>
                                    <select class="form-select" name="apartment_models[{{ $i }}][view_type]">
                                        @php($rowView = $model['view_type'] ?? $defaultFacingCode)
                                        @forelse ($facingsList as $facing)
                                            <option value="{{ $facing->code }}" @selected($rowView === $facing->code)>{{ $facing->name }}</option>
                                        @empty
                                            <option value="normal" @selected($rowView === 'normal')>عادية</option>
                                        @endforelse
                                    </select>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-outline-danger btn-sm remove-model-row">حذف</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="price" value="{{ old('price', $property->price ?? 0) }}">
<input type="hidden" name="status" value="{{ old('status', $property->status ?? 'available') }}">
<input type="hidden" name="owner_id" value="{{ old('owner_id', $property->owner_id ?? '') }}">

@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<script>
    (function () {
        const landsData = @json($landsData);
        const facingsOptions = @json($facingsList->map(fn ($f) => ['code' => $f->code, 'name' => $f->name])->values());
        const landSelect = document.getElementById('land_id');
        const landNameInput = document.getElementById('land_name');
        const areaSelect = document.querySelector('select[name="area_id"]');
        const floors = document.getElementById('floors_count');
        const buildingTotalFloors = document.getElementById('building_total_floors');
        const apartments = document.getElementById('apartments_per_floor');
        const mezzanineFloorsBody = document.getElementById('mezzanine-floors-body');
        const addMezzanineRowBtn = document.getElementById('add-mezzanine-row');
        const total = document.getElementById('total_apartments');
        const groundFloorShops = document.getElementById('ground_floor_shops_count');
        const recalculateTotalBtn = document.getElementById('recalculate-total');
        const registeredFloorsBox = document.getElementById('registered-floors-box');
        const mushaaFloorsBox = document.getElementById('mushaa-floors-box');
        const modelsBody = document.getElementById('apartment-models-body');
        const addModelBtn = document.getElementById('add-model-row');
        let totalIsManual = false;
        const syncFromSelectedLand = () => {
            if (!landSelect) {
                return;
            }

            const selected = landsData[String(landSelect.value || '')];
            if (!selected) {
                return;
            }

            if (landNameInput && (!landNameInput.value || landNameInput.value === '0')) {
                landNameInput.value = selected.name || '';
            }

            if (areaSelect && selected.area_id) {
                areaSelect.value = String(selected.area_id);
            }

            const costKeys = [
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
            ];
            costKeys.forEach((key) => {
                const input = document.getElementById(key);
                if (!input) {
                    return;
                }
                input.value = String(selected[key] ?? 0);
            });
        };

        const initiallySelectedRegisteredFloors = new Set(@json($registeredFloors));
        const initiallySelectedMushaaFloors = new Set(@json($mushaaFloors));

        const allowedFloorsForMushaa = () => {
            const allowed = new Set();
            if (registeredFloorsBox) {
                registeredFloorsBox.querySelectorAll('input[name="registered_floors[]"]:checked').forEach((input) => {
                    const n = parseInt(input.value || '0', 10);
                    if (n > 0) {
                        allowed.add(n);
                    }
                });
            }
            if (mezzanineFloorsBody) {
                mezzanineFloorsBody.querySelectorAll('tr').forEach((tr) => {
                    const numInput = tr.querySelector('input[name*="[floor_number]"]');
                    const n = parseInt(numInput?.value || '0', 10);
                    if (n >= 1) {
                        allowed.add(n);
                    }
                });
            }
            return allowed;
        };

        const syncMushaaFloors = () => {
            if (!mushaaFloorsBox || !buildingTotalFloors) {
                return;
            }
            const maxFloor = Math.max(1, parseInt(buildingTotalFloors.value || '1', 10));
            const currentSelected = Array.from(mushaaFloorsBox.querySelectorAll('input[type="checkbox"][name="mushaa_floors[]"]:checked'))
                .map((input) => parseInt(input.value || '0', 10))
                .filter((value) => value > 0);
            const selectedSet = currentSelected.length ? new Set(currentSelected) : new Set(initiallySelectedMushaaFloors);
            const allowed = allowedFloorsForMushaa();

            mushaaFloorsBox.innerHTML = '';
            for (let i = 1; i <= maxFloor; i++) {
                const wrapper = document.createElement('label');
                wrapper.className = 'btn btn-outline-info btn-sm';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input me-1';
                checkbox.name = 'mushaa_floors[]';
                checkbox.value = String(i);
                const isAllowed = allowed.has(i);
                checkbox.disabled = !isAllowed;
                checkbox.checked = isAllowed && selectedSet.has(i);
                if (!isAllowed) {
                    checkbox.title = 'فعّل هذا الدور أولًا في «الأدوار المسجلة» أو أدخل رقمه في «الميزان»';
                    wrapper.classList.add('opacity-50');
                }
                wrapper.appendChild(checkbox);
                wrapper.appendChild(document.createTextNode(`دور ${i} مشاع`));
                mushaaFloorsBox.appendChild(wrapper);
            }
        };

        const syncRegisteredFloors = () => {
            if (!registeredFloorsBox || !buildingTotalFloors || !floors) {
                return;
            }

            const maxFloor = Math.max(1, parseInt(buildingTotalFloors.value || '1', 10));
            const mezzanineFloorsSet = new Set();
            if (mezzanineFloorsBody) {
                mezzanineFloorsBody.querySelectorAll('tr').forEach((tr) => {
                    const numInput = tr.querySelector('input[name*="[floor_number]"]');
                    const n = parseInt(numInput?.value || '0', 10);
                    if (n >= 1) {
                        mezzanineFloorsSet.add(n);
                    }
                });
            }
            const currentSelected = Array.from(registeredFloorsBox.querySelectorAll('input[type="checkbox"]:checked'))
                .map((input) => parseInt(input.value || '0', 10))
                .filter((value) => value > 0);
            const selectedSet = currentSelected.length ? new Set(currentSelected) : new Set(initiallySelectedRegisteredFloors);

            registeredFloorsBox.innerHTML = '';
            for (let i = 1; i <= maxFloor; i++) {
                const wrapper = document.createElement('label');
                wrapper.className = 'btn btn-outline-secondary btn-sm';
                const checkbox = document.createElement('input');
                checkbox.type = 'checkbox';
                checkbox.className = 'form-check-input me-1';
                checkbox.name = 'registered_floors[]';
                checkbox.value = String(i);
                checkbox.checked = selectedSet.has(i);
                checkbox.addEventListener('change', () => {
                    const count = registeredFloorsBox.querySelectorAll('input[type="checkbox"]:checked').length;
                    floors.value = String(Math.max(1, count));
                    syncMushaaFloors();
                    syncTotal();
                });

                wrapper.appendChild(checkbox);
                const labelText = mezzanineFloorsSet.has(i) ? `دور ${i} (سكني)` : `دور ${i}`;
                wrapper.appendChild(document.createTextNode(labelText));
                registeredFloorsBox.appendChild(wrapper);
            }

            const selectedCount = registeredFloorsBox.querySelectorAll('input[type="checkbox"]:checked').length;
            floors.value = String(Math.max(1, selectedCount));
            syncMushaaFloors();
        };

        const syncTotal = () => {
            if (totalIsManual) {
                return;
            }

            const selectedFloorsCount = registeredFloorsBox
                ? registeredFloorsBox.querySelectorAll('input[type="checkbox"]:checked').length
                : 0;
            const floorsValue = Math.max(1, selectedFloorsCount || parseInt(floors?.value || '1', 10));
            const apartmentsValue = Math.max(1, parseInt(apartments?.value || '1', 10));
            const mezzanineValue = mezzanineFloorsBody
                ? Array.from(mezzanineFloorsBody.querySelectorAll('tr')).reduce((acc, tr) => {
                    const floorInput = tr.querySelector('input[name*="[floor_number]"]');
                    const countInput = tr.querySelector('input[name*="[apartments_count]"]');
                    const floorNum = parseInt(floorInput?.value || '0', 10);
                    const countNum = parseInt(countInput?.value || '0', 10);
                    if (floorNum < 1 || countNum < 1) {
                        return acc;
                    }

                    return acc + countNum;
                }, 0)
                : 0;
            const shopsValue = Math.max(0, parseInt(groundFloorShops?.value || '0', 10));
            if (total) {
                total.value = String((floorsValue * apartmentsValue) + mezzanineValue + shopsValue);
            }
        };

        floors?.addEventListener('input', syncTotal);
        buildingTotalFloors?.addEventListener('input', () => {
            syncRegisteredFloors();
            syncMushaaFloors();
            syncTotal();
        });
        apartments?.addEventListener('input', syncTotal);
        groundFloorShops?.addEventListener('input', syncTotal);
        mezzanineFloorsBody?.addEventListener('input', () => {
            syncRegisteredFloors();
            syncMushaaFloors();
            syncTotal();
        });
        total?.addEventListener('input', () => {
            totalIsManual = true;
        });
        recalculateTotalBtn?.addEventListener('click', () => {
            totalIsManual = false;
            syncTotal();
        });

        addMezzanineRowBtn?.addEventListener('click', () => {
            if (!mezzanineFloorsBody) {
                return;
            }

            const index = mezzanineFloorsBody.querySelectorAll('tr').length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[${index}][floor_number]" placeholder="مثال: 12"></td>
                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[${index}][apartments_count]" placeholder="مثال: 2"></td>
                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-mezzanine-row">حذف</button></td>
            `;
            mezzanineFloorsBody.appendChild(tr);
            syncRegisteredFloors();
            syncMushaaFloors();
        });

        mezzanineFloorsBody?.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof HTMLElement && target.classList.contains('remove-mezzanine-row')) {
                target.closest('tr')?.remove();
                syncRegisteredFloors();
                syncMushaaFloors();
                syncTotal();
            }
        });

        addModelBtn?.addEventListener('click', () => {
            const index = modelsBody.querySelectorAll('tr').length;
            const tr = document.createElement('tr');

            const tdName = document.createElement('td');
            tdName.innerHTML = `<input type="text" class="form-control" name="apartment_models[${index}][model_name]" placeholder="مثال: نموذج A">`;
            const tdArea = document.createElement('td');
            tdArea.innerHTML = `<input type="number" step="0.01" min="1" class="form-control" name="apartment_models[${index}][area]" placeholder="مثال: 120">`;
            const tdRooms = document.createElement('td');
            tdRooms.innerHTML = `<input type="number" min="0" class="form-control" name="apartment_models[${index}][rooms_count]" placeholder="مثال: 3">`;
            const tdBaths = document.createElement('td');
            tdBaths.innerHTML = `<input type="number" min="0" class="form-control" name="apartment_models[${index}][bathrooms_count]" placeholder="مثال: 2">`;

            const tdView = document.createElement('td');
            const viewSelect = document.createElement('select');
            viewSelect.className = 'form-select';
            viewSelect.name = `apartment_models[${index}][view_type]`;
            const opts = Array.isArray(facingsOptions) && facingsOptions.length > 0
                ? facingsOptions
                : [{ code: 'normal', name: 'عادية' }];
            opts.forEach((f) => {
                const o = document.createElement('option');
                o.value = f.code;
                o.textContent = f.name;
                viewSelect.appendChild(o);
            });
            tdView.appendChild(viewSelect);

            const tdDel = document.createElement('td');
            tdDel.className = 'text-end';
            tdDel.innerHTML = '<button type="button" class="btn btn-outline-danger btn-sm remove-model-row">حذف</button>';

            tr.append(tdName, tdArea, tdRooms, tdBaths, tdView, tdDel);
            modelsBody.appendChild(tr);
        });

        modelsBody?.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof HTMLElement && target.classList.contains('remove-model-row')) {
                target.closest('tr')?.remove();
            }
        });

        landSelect?.addEventListener('change', syncFromSelectedLand);

        syncRegisteredFloors();
        syncMushaaFloors();
        syncTotal();
    })();
</script>
