@csrf

@php
    $selectedPropertyId = (string) old('property_id', $sale->property_id ?? '');
    $selectedFloor = (int) old('floor_number', $sale->floor_number ?? 1);
    $selectedIsMezzanine = filter_var(
        old('is_mezzanine', ($sale->is_mezzanine ?? false) ? '1' : '0'),
        FILTER_VALIDATE_BOOL
    );
    $selectedModel = (string) old('apartment_model', $sale->apartment_model ?? '');
    $paymentType = old('payment_type', $sale->payment_type ?? 'cash');
    $installmentSchedule = old('installment_schedule', ($sale->installment_plan ?? [])['schedule_type'] ?? 'monthly');
    $secondaryPayments = old('secondary_payments');
    if (! is_array($secondaryPayments)) {
        $savedPlan = $sale->installment_plan ?? [];
        $secondaryPayments = ($sale->exists && is_array($savedPlan['secondary_payments'] ?? null))
            ? $savedPlan['secondary_payments']
            : [];
    }
    if ($secondaryPayments === []) {
        $secondaryPayments = [['label' => '', 'amount' => '', 'due_date' => '']];
    }
    $salePriceValue = (float) old('sale_price', $sale->sale_price ?? 0);
    $downPaymentValue = (float) old('down_payment', $sale->down_payment ?? 0);
    $downPaymentPercentageValue = (float) old(
        'down_payment_percentage',
        $salePriceValue > 0 ? round(($downPaymentValue / $salePriceValue) * 100, 2) : ($paymentType === 'cash' ? 100 : 0)
    );
    $propertiesMeta = $properties->mapWithKeys(function ($p) {
        $registeredFloors = collect($p->registered_floors ?? [])
            ->map(static fn ($value) => (int) $value)
            ->filter(static fn (int $value) => $value >= 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
        $mezzanineFloors = collect($p->mezzanine_floors ?? [])
            ->filter(static fn ($item) => is_array($item) && !empty($item['floor_number']))
            ->map(static fn (array $item) => [
                'floor_number' => (int) ($item['floor_number'] ?? 0),
                'apartments_count' => (int) ($item['apartments_count'] ?? 0),
            ])
            ->filter(static fn (array $item) => $item['floor_number'] >= 1)
            ->unique('floor_number')
            ->sortBy('floor_number')
            ->values()
            ->all();
        $mushaaFloors = collect($p->mushaa_floors ?? [])
            ->map(static fn ($n) => (int) $n)
            ->filter(static fn (int $n) => $n >= 1)
            ->unique()
            ->sort()
            ->values()
            ->all();
        if ($mushaaFloors === []) {
            $mushaaFloors = collect($p->mezzanine_floors ?? [])
                ->filter(static fn ($row) => is_array($row) && filter_var($row['is_mushaa'] ?? false, FILTER_VALIDATE_BOOL))
                ->map(static fn ($row) => (int) ($row['floor_number'] ?? 0))
                ->filter(static fn (int $n) => $n >= 1)
                ->unique()
                ->sort()
                ->values()
                ->all();
        }

        return [(string) $p->id => [
            'floors_count' => (int) ($p->floors_count ?? 1),
            'registered_floors' => $registeredFloors,
            'ground_floor_shops_count' => (int) ($p->ground_floor_shops_count ?? 0),
            'mezzanine_floors' => $mezzanineFloors,
            'mushaa_floors' => $mushaaFloors,
            'models' => collect($p->apartment_models ?? [])->pluck('model_name')->filter()->values()->all(),
        ]];
    });
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">العقار</label>
        <select name="property_id" id="property_id" class="form-select" required>
            <option value="">اختر العقار</option>
            @foreach ($properties as $property)
                <option value="{{ $property->id }}" @selected($selectedPropertyId === (string) $property->id)>{{ $property->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">الدور</label>
        <select name="floor_number" id="floor_number" class="form-select" required></select>
        <input type="hidden" name="is_mezzanine" id="is_mezzanine_input" value="{{ $selectedIsMezzanine ? '1' : '0' }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">النموذج</label>
        <select name="apartment_model" id="apartment_model" class="form-select" required></select>
    </div>

    <div class="col-md-3">
        <label class="form-label">سعر البيع</label>
        <input type="number" step="0.01" min="1" name="sale_price" id="sale_price" class="form-control"
               value="{{ old('sale_price', $sale->sale_price ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">نوع السداد</label>
        <select name="payment_type" id="payment_type" class="form-select" required>
            <option value="cash" @selected($paymentType === 'cash')>كاش</option>
            <option value="installment" @selected($paymentType === 'installment')>تقسيط</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">نسبة المقدم (%)</label>
        <input type="number" step="0.01" min="0" max="100" id="down_payment_percentage" class="form-control"
               value="{{ $downPaymentPercentageValue }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">المقدم</label>
        <input type="number" step="0.01" min="0" name="down_payment" id="down_payment" class="form-control"
               value="{{ old('down_payment', $sale->down_payment ?? '') }}">
        <small class="text-muted">المقدم = سعر الوحدة × نسبة المقدم</small>
    </div>

    <div class="col-md-4 installment-field">
        <label class="form-label">مدة التقسيط (شهور)</label>
        <input type="number" min="1" name="installment_months" class="form-control"
               value="{{ old('installment_months', $sale->installment_months ?? '') }}">
    </div>
    <div class="col-md-4 installment-field">
        <label class="form-label">نظام القسط</label>
        <select name="installment_schedule" id="installment_schedule" class="form-select">
            <option value="monthly" @selected($installmentSchedule === 'monthly')>شهري (كل شهر)</option>
            <option value="quarterly" @selected($installmentSchedule === 'quarterly')>كل 3 شهور</option>
            <option value="semiannual" @selected($installmentSchedule === 'semiannual')>كل 6 شهور</option>
        </select>
    </div>
    <div class="col-md-4 installment-field">
        <label class="form-label">بداية أول قسط</label>
        <input type="date" name="installment_start_date" class="form-control"
               value="{{ old('installment_start_date', isset($sale->installment_start_date) ? $sale->installment_start_date->format('Y-m-d') : '') }}">
    </div>

    <div class="col-12 installment-field">
        <h6 class="mb-2 text-body-secondary">دفعات ثانوية <span class="fw-normal text-muted small">(اختياري)</span></h6>
        <p class="small text-muted mb-2">
            مبالغ بتاريخ استحقاق محدد تُخصم من المتبقي بعد المقدم، ثم يُقسّط ما تبقى على أقساط النظام المختار (شهري / كل 3 شهور / كل 6 شهور).
        </p>
        <div id="secondary-payments-rows" class="border rounded p-2 bg-body-tertiary">
            @foreach ($secondaryPayments as $idx => $sp)
                <div class="row g-2 align-items-end secondary-pay-row mb-2">
                    <div class="col-md-4">
                        <label class="form-label small mb-0">الوصف</label>
                        <input type="text" class="form-control form-control-sm" data-field="label"
                               name="secondary_payments[{{ $idx }}][label]"
                               value="{{ old("secondary_payments.$idx.label", $sp['label'] ?? '') }}"
                               maxlength="255" placeholder="مثال: دفعة تشطيب">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">المبلغ (ج.م)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" data-field="amount"
                               name="secondary_payments[{{ $idx }}][amount]"
                               value="{{ old("secondary_payments.$idx.amount", $sp['amount'] ?? '') }}"
                               placeholder="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">تاريخ الاستحقاق</label>
                        <input type="date" class="form-control form-control-sm" data-field="due_date"
                               name="secondary_payments[{{ $idx }}][due_date]"
                               value="{{ old("secondary_payments.$idx.due_date", isset($sp['due_date']) ? (string) $sp['due_date'] : '') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-secondary-pay" title="حذف الصف">حذف</button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" id="add-secondary-payment">+ إضافة دفعة ثانوية</button>
    </div>
    <div class="col-md-4">
        <label class="form-label">تاريخ البيعة</label>
        <input type="date" name="sale_date" class="form-control"
               value="{{ old('sale_date', isset($sale->sale_date) ? $sale->sale_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>
    <div class="col-md-8">
        <label class="form-label">اسم البروكر (من نفّذ البيع)</label>
        <input type="text" name="broker_name" class="form-control" maxlength="255"
               value="{{ old('broker_name', $sale->broker_name ?? '') }}" required
               placeholder="اسم البروكر">
    </div>

    <div class="col-12"><hr class="my-2"></div>
    <div class="col-12"><h6 class="mb-0">بيانات العميل</h6></div>
    <div class="col-md-3">
        <label class="form-label">اسم العميل</label>
        <input type="text" name="client_name" class="form-control" value="{{ old('client_name', $sale->client?->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">الهاتف</label>
        <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone', $sale->client?->phone ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">البريد الإلكتروني</label>
        <input type="email" name="client_email" class="form-control" value="{{ old('client_email', $sale->client?->email ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">الرقم القومي</label>
        <input type="text" name="client_national_id" class="form-control" value="{{ old('client_national_id', $sale->client?->national_id ?? '') }}">
    </div>

    <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $sale->notes ?? '') }}</textarea>
    </div>
</div>

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
        const propertySelect = document.getElementById('property_id');
        const floorSelect = document.getElementById('floor_number');
        const modelSelect = document.getElementById('apartment_model');
        const paymentType = document.getElementById('payment_type');
        const installmentSchedule = document.getElementById('installment_schedule');
        const secondaryRowsWrap = document.getElementById('secondary-payments-rows');
        const salePriceInput = document.getElementById('sale_price');
        const downPaymentInput = document.getElementById('down_payment');
        const downPaymentPercentageInput = document.getElementById('down_payment_percentage');
        const installmentFields = document.querySelectorAll('.installment-field');

        const selectedFloor = {{ $selectedFloor }};
        const selectedIsMezzanine = @json($selectedIsMezzanine);
        const selectedModel = @json($selectedModel);
        const properties = @json($propertiesMeta);

        function syncMezzanineHiddenFromFloor() {
            const hidden = document.getElementById('is_mezzanine_input');
            const opt = floorSelect?.selectedOptions?.[0];
            if (!hidden || !opt) {
                return;
            }
            hidden.value = opt.dataset.isMezzanine === '1' ? '1' : '0';
        }

        function reindexSecondaryPaymentRows() {
            if (!secondaryRowsWrap) {
                return;
            }
            secondaryRowsWrap.querySelectorAll('.secondary-pay-row').forEach((row, i) => {
                row.querySelectorAll('[data-field]').forEach((el) => {
                    const field = el.getAttribute('data-field');
                    el.name = `secondary_payments[${i}][${field}]`;
                });
            });
        }

        function ensureAtLeastOneSecondaryRow() {
            if (!secondaryRowsWrap) {
                return;
            }
            if (!secondaryRowsWrap.querySelector('.secondary-pay-row')) {
                const row = document.createElement('div');
                row.className = 'row g-2 align-items-end secondary-pay-row mb-2';
                row.innerHTML = `
                    <div class="col-md-4">
                        <label class="form-label small mb-0">الوصف</label>
                        <input type="text" class="form-control form-control-sm" data-field="label" maxlength="255" placeholder="مثال: دفعة تشطيب">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">المبلغ (ج.م)</label>
                        <input type="number" step="0.01" min="0.01" class="form-control form-control-sm" data-field="amount" placeholder="0">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-0">تاريخ الاستحقاق</label>
                        <input type="date" class="form-control form-control-sm" data-field="due_date">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm w-100 remove-secondary-pay" title="حذف الصف">حذف</button>
                    </div>`;
                secondaryRowsWrap.appendChild(row);
                bindSecondaryRowButtons(row);
                reindexSecondaryPaymentRows();
            }
        }

        function bindSecondaryRowButtons(row) {
            row.querySelector('.remove-secondary-pay')?.addEventListener('click', () => {
                row.remove();
                ensureAtLeastOneSecondaryRow();
                reindexSecondaryPaymentRows();
            });
        }

        function refreshPropertyMeta() {
            const id = propertySelect?.value;
            const meta = properties[id] || {
                floors_count: 1,
                registered_floors: [],
                ground_floor_shops_count: 0,
                mezzanine_floors: [],
                mushaa_floors: [],
                models: []
            };

            floorSelect.innerHTML = '';
            const mezzanineNums = new Set(
                (Array.isArray(meta.mezzanine_floors) ? meta.mezzanine_floors : [])
                    .map((row) => Number(row.floor_number || 0))
                    .filter((n) => n >= 1)
            );
            const residentialList = Array.isArray(meta.registered_floors) && meta.registered_floors.length
                ? meta.registered_floors.map((n) => Number(n))
                : Array.from({ length: Math.max(1, meta.floors_count || 1) }, (_, idx) => idx + 1);

            const pairKeys = new Set();
            const floorOptions = [];
            const pushFloorOption = (value, isMezzanine) => {
                const k = `${value}:${isMezzanine ? 1 : 0}`;
                if (pairKeys.has(k)) {
                    return;
                }
                pairKeys.add(k);
                let label;
                if (isMezzanine) {
                    label = `الدور ${value} (ميزان)`;
                } else if (mezzanineNums.has(value)) {
                    label = `الدور ${value} (سكني)`;
                } else {
                    label = String(value);
                }
                floorOptions.push({ value, label, isMezzanine });
            };

            if (meta.ground_floor_shops_count > 0) {
                pushFloorOption(0, false);
            }
            residentialList.forEach((n) => pushFloorOption(Number(n), false));
            mezzanineNums.forEach((n) => pushFloorOption(Number(n), true));

            floorOptions.sort((a, b) => {
                if (a.value !== b.value) {
                    return Number(a.value) - Number(b.value);
                }
                return Number(a.isMezzanine) - Number(b.isMezzanine);
            });

            const mushaaSet = new Set((meta.mushaa_floors || []).map((n) => Number(n)));
            floorOptions.forEach((opt) => {
                if (opt.value < 1 || !mushaaSet.has(opt.value)) {
                    return;
                }
                if (opt.label.includes('مشاع')) {
                    return;
                }
                if (opt.label.includes('ميزان')) {
                    opt.label = opt.label.replace('(ميزان)', '(ميزان · مشاع)');
                } else if (opt.label.includes('سكني')) {
                    opt.label = opt.label.replace('(سكني)', '(سكني · مشاع)');
                } else {
                    opt.label = `${opt.label} (مشاع)`;
                }
            });

            floorOptions.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.value);
                option.textContent = item.label;
                option.dataset.isMezzanine = item.isMezzanine ? '1' : '0';
                const floorNum = Number(item.value);
                const selFloor = Number(selectedFloor);
                if (floorNum === selFloor && Boolean(item.isMezzanine) === Boolean(selectedIsMezzanine)) {
                    option.selected = true;
                }
                floorSelect.appendChild(option);
            });

            if (floorSelect.selectedIndex < 0 && floorSelect.options.length) {
                floorSelect.selectedIndex = 0;
            }
            syncMezzanineHiddenFromFloor();

            modelSelect.innerHTML = '';
            const models = meta.models.length ? meta.models : ['نموذج افتراضي'];
            models.forEach((model) => {
                const option = document.createElement('option');
                option.value = model;
                option.textContent = model;
                if (model === selectedModel) option.selected = true;
                modelSelect.appendChild(option);
            });
        }

        function refreshPaymentType() {
            const isInstallment = paymentType?.value === 'installment';
            installmentFields.forEach((field) => {
                field.style.display = isInstallment ? '' : 'none';
            });

            if (!downPaymentInput || !downPaymentPercentageInput || !salePriceInput) {
                return;
            }

            const price = Math.max(0, parseFloat(salePriceInput.value || '0'));
            if (!isInstallment) {
                downPaymentPercentageInput.value = '100';
                downPaymentInput.value = String(price);
                downPaymentInput.readOnly = true;
                downPaymentPercentageInput.readOnly = true;
                return;
            }

            downPaymentInput.readOnly = false;
            downPaymentPercentageInput.readOnly = false;
        }

        function clampPercent(value) {
            return Math.min(100, Math.max(0, value));
        }

        function recalcDownPaymentFromPercentage() {
            if (!salePriceInput || !downPaymentInput || !downPaymentPercentageInput) {
                return;
            }

            const price = Math.max(0, parseFloat(salePriceInput.value || '0'));
            const percent = clampPercent(parseFloat(downPaymentPercentageInput.value || '0'));
            downPaymentPercentageInput.value = String(percent);
            downPaymentInput.value = String(Math.round((price * (percent / 100)) * 100) / 100);
        }

        function recalcPercentageFromDownPayment() {
            if (!salePriceInput || !downPaymentInput || !downPaymentPercentageInput) {
                return;
            }

            const price = Math.max(0, parseFloat(salePriceInput.value || '0'));
            const down = Math.max(0, parseFloat(downPaymentInput.value || '0'));
            if (price <= 0) {
                downPaymentPercentageInput.value = '0';
                return;
            }

            downPaymentPercentageInput.value = String(Math.round((down / price) * 10000) / 100);
        }

        document.getElementById('add-secondary-payment')?.addEventListener('click', () => {
            const first = secondaryRowsWrap?.querySelector('.secondary-pay-row');
            if (!first || !secondaryRowsWrap) {
                return;
            }
            const row = first.cloneNode(true);
            row.querySelectorAll('input').forEach((el) => {
                el.value = '';
            });
            secondaryRowsWrap.appendChild(row);
            bindSecondaryRowButtons(row);
            reindexSecondaryPaymentRows();
        });

        secondaryRowsWrap?.querySelectorAll('.secondary-pay-row').forEach((row) => bindSecondaryRowButtons(row));
        reindexSecondaryPaymentRows();

        propertySelect?.addEventListener('change', refreshPropertyMeta);
        floorSelect?.addEventListener('change', syncMezzanineHiddenFromFloor);
        paymentType?.addEventListener('change', refreshPaymentType);
        installmentSchedule?.addEventListener('change', refreshPaymentType);
        salePriceInput?.addEventListener('input', recalcDownPaymentFromPercentage);
        downPaymentPercentageInput?.addEventListener('input', recalcDownPaymentFromPercentage);
        downPaymentInput?.addEventListener('input', () => {
            if (paymentType?.value === 'installment') {
                recalcPercentageFromDownPayment();
            }
        });

        refreshPropertyMeta();
        refreshPaymentType();
        if (paymentType?.value === 'installment') {
            recalcPercentageFromDownPayment();
        }
    })();
</script>
