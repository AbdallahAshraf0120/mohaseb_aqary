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

    $apartmentModels = old('apartment_models', $property->apartment_models ?? [[
        'model_name' => '',
        'area' => '',
        'rooms_count' => '',
        'bathrooms_count' => '',
        'view_type' => 'normal',
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
        <label class="form-label">المنطقة</label>
        <select name="area_id" class="form-select" required>
            <option value="">اختر المنطقة</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected((string) old('area_id', $property->area_id ?? '') === (string) $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
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
        <input type="number" min="0" name="ground_floor_shops_count" class="form-control"
               value="{{ old('ground_floor_shops_count', $property->ground_floor_shops_count ?? 0) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label d-flex justify-content-between align-items-center">
            <span>إجمالي الشقق بالعقار</span>
            <button type="button" class="btn btn-link btn-sm p-0" id="recalculate-total">إعادة الحساب</button>
        </label>
        <input type="number" min="1" name="total_apartments" id="total_apartments" class="form-control"
               value="{{ old('total_apartments', $property->total_apartments ?? 1) }}" required>
    </div>

    <div class="col-12">
        <div class="card border">
            <div class="card-header py-2"><strong>اختيار الأدوار المسجلة بالعقار</strong></div>
            <div class="card-body">
                <p class="text-muted small mb-2">حدد الأدوار التي تملكها/ستسجلها (مثال: من 12 دور تختار 6 أدوار فقط).</p>
                <div class="d-flex flex-wrap gap-2" id="registered-floors-box"></div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card border">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong>أدوار الميزان (أكثر من ميزان)</strong>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-mezzanine-row">إضافة ميزان</button>
            </div>
            <div class="card-body">
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
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[{{ $i }}][floor_number]" value="{{ $mezz['floor_number'] ?? '' }}" placeholder="مثال: 1"></td>
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[{{ $i }}][apartments_count]" value="{{ $mezz['apartments_count'] ?? '' }}" placeholder="مثال: 2"></td>
                                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-mezzanine-row">حذف</button></td>
                            </tr>
                        @empty
                            <tr>
                                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[0][floor_number]" placeholder="مثال: 1"></td>
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
        <div class="card border">
            <div class="card-header py-2"><strong>نسبة كل مساهم في العقار</strong></div>
            <div class="card-body">
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
        <div class="card border">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <strong>نماذج الشقق (المساحة = نموذج)</strong>
                <button type="button" class="btn btn-outline-primary btn-sm" id="add-model-row">إضافة نموذج</button>
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
                                        <option value="normal" @selected(($model['view_type'] ?? 'normal') === 'normal')>عادية</option>
                                        <option value="facade" @selected(($model['view_type'] ?? '') === 'facade')>واجهة</option>
                                        <option value="corner" @selected(($model['view_type'] ?? '') === 'corner')>ناصية</option>
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
        const floors = document.getElementById('floors_count');
        const buildingTotalFloors = document.getElementById('building_total_floors');
        const apartments = document.getElementById('apartments_per_floor');
        const mezzanineFloorsBody = document.getElementById('mezzanine-floors-body');
        const addMezzanineRowBtn = document.getElementById('add-mezzanine-row');
        const total = document.getElementById('total_apartments');
        const recalculateTotalBtn = document.getElementById('recalculate-total');
        const registeredFloorsBox = document.getElementById('registered-floors-box');
        const modelsBody = document.getElementById('apartment-models-body');
        const addModelBtn = document.getElementById('add-model-row');
        let totalIsManual = false;
        const initiallySelectedRegisteredFloors = new Set(@json($registeredFloors));

        const syncRegisteredFloors = () => {
            if (!registeredFloorsBox || !buildingTotalFloors || !floors) {
                return;
            }

            const maxFloor = Math.max(1, parseInt(buildingTotalFloors.value || '1', 10));
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
                    syncTotal();
                });

                wrapper.appendChild(checkbox);
                wrapper.appendChild(document.createTextNode(`دور ${i}`));
                registeredFloorsBox.appendChild(wrapper);
            }

            const selectedCount = registeredFloorsBox.querySelectorAll('input[type="checkbox"]:checked').length;
            floors.value = String(Math.max(1, selectedCount));
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
                ? Array.from(mezzanineFloorsBody.querySelectorAll('input[name*="[apartments_count]"]'))
                    .map((input) => Math.max(0, parseInt(input.value || '0', 10)))
                    .reduce((acc, current) => acc + current, 0)
                : 0;
            if (total) {
                total.value = String((floorsValue * apartmentsValue) + mezzanineValue);
            }
        };

        floors?.addEventListener('input', syncTotal);
        buildingTotalFloors?.addEventListener('input', () => {
            syncRegisteredFloors();
            syncTotal();
        });
        apartments?.addEventListener('input', syncTotal);
        mezzanineFloorsBody?.addEventListener('input', syncTotal);
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
                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[${index}][floor_number]" placeholder="مثال: 1"></td>
                <td><input type="number" min="1" class="form-control" name="mezzanine_floors[${index}][apartments_count]" placeholder="مثال: 2"></td>
                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-mezzanine-row">حذف</button></td>
            `;
            mezzanineFloorsBody.appendChild(tr);
        });

        mezzanineFloorsBody?.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof HTMLElement && target.classList.contains('remove-mezzanine-row')) {
                target.closest('tr')?.remove();
                syncTotal();
            }
        });

        addModelBtn?.addEventListener('click', () => {
            const index = modelsBody.querySelectorAll('tr').length;
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td><input type="text" class="form-control" name="apartment_models[${index}][model_name]" placeholder="مثال: نموذج A"></td>
                <td><input type="number" step="0.01" min="1" class="form-control" name="apartment_models[${index}][area]" placeholder="مثال: 120"></td>
                <td><input type="number" min="0" class="form-control" name="apartment_models[${index}][rooms_count]" placeholder="مثال: 3"></td>
                <td><input type="number" min="0" class="form-control" name="apartment_models[${index}][bathrooms_count]" placeholder="مثال: 2"></td>
                <td>
                    <select class="form-select" name="apartment_models[${index}][view_type]">
                        <option value="normal">عادية</option>
                        <option value="facade">واجهة</option>
                        <option value="corner">ناصية</option>
                    </select>
                </td>
                <td class="text-end"><button type="button" class="btn btn-outline-danger btn-sm remove-model-row">حذف</button></td>
            `;
            modelsBody.appendChild(tr);
        });

        modelsBody?.addEventListener('click', (event) => {
            const target = event.target;
            if (target instanceof HTMLElement && target.classList.contains('remove-model-row')) {
                target.closest('tr')?.remove();
            }
        });

        syncRegisteredFloors();
        syncTotal();
    })();
</script>
