@csrf

@php
    $selectedPropertyId = (string) old('property_id', $sale->property_id ?? '');
    $selectedFloor = (int) old('floor_number', $sale->floor_number ?? 1);
    $selectedModel = (string) old('apartment_model', $sale->apartment_model ?? '');
    $paymentType = old('payment_type', $sale->payment_type ?? 'cash');
    $installmentSchedule = old('installment_schedule', $sale->installment_plan['schedule_type'] ?? 'monthly');
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

        return [(string) $p->id => [
            'floors_count' => (int) ($p->floors_count ?? 1),
            'registered_floors' => $registeredFloors,
            'ground_floor_shops_count' => (int) ($p->ground_floor_shops_count ?? 0),
            'mezzanine_floors' => $mezzanineFloors,
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
        </select>
    </div>
    <div class="col-md-4 installment-field">
        <label class="form-label">بداية أول قسط</label>
        <input type="date" name="installment_start_date" class="form-control"
               value="{{ old('installment_start_date', isset($sale->installment_start_date) ? $sale->installment_start_date->format('Y-m-d') : '') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">تاريخ البيعة</label>
        <input type="date" name="sale_date" class="form-control"
               value="{{ old('sale_date', isset($sale->sale_date) ? $sale->sale_date->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>

    <div class="col-12"><hr class="my-2"></div>
    <div class="col-12"><h6 class="mb-0">بيانات العميل</h6></div>
    <div class="col-md-3">
        <label class="form-label">اسم العميل</label>
        <input type="text" name="client_name" class="form-control" value="{{ old('client_name', $sale->client->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">الهاتف</label>
        <input type="text" name="client_phone" class="form-control" value="{{ old('client_phone', $sale->client->phone ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">البريد الإلكتروني</label>
        <input type="email" name="client_email" class="form-control" value="{{ old('client_email', $sale->client->email ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">الرقم القومي</label>
        <input type="text" name="client_national_id" class="form-control" value="{{ old('client_national_id', $sale->client->national_id ?? '') }}">
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
        const salePriceInput = document.getElementById('sale_price');
        const downPaymentInput = document.getElementById('down_payment');
        const downPaymentPercentageInput = document.getElementById('down_payment_percentage');
        const installmentFields = document.querySelectorAll('.installment-field');

        const selectedFloor = {{ $selectedFloor }};
        const selectedModel = @json($selectedModel);
        const properties = @json($propertiesMeta);

        function refreshPropertyMeta() {
            const id = propertySelect?.value;
            const meta = properties[id] || {
                floors_count: 1,
                registered_floors: [],
                ground_floor_shops_count: 0,
                mezzanine_floors: [],
                models: []
            };

            floorSelect.innerHTML = '';
            const registeredFloors = Array.isArray(meta.registered_floors) && meta.registered_floors.length
                ? meta.registered_floors
                : Array.from({ length: Math.max(1, meta.floors_count) }, (_, idx) => idx + 1);
            const floorOptions = [];
            if (meta.ground_floor_shops_count > 0) {
                floorOptions.push({ value: 0, label: '0 (أرضي تجاري)' });
            }
            registeredFloors.forEach((floorNumber) => {
                floorOptions.push({ value: floorNumber, label: String(floorNumber) });
            });
            if (Array.isArray(meta.mezzanine_floors)) {
                meta.mezzanine_floors.forEach((item) => {
                    const floorNumber = Number(item.floor_number || 0);
                    if (floorNumber < 1) {
                        return;
                    }
                    const existing = floorOptions.find((option) => option.value === floorNumber);
                    if (existing) {
                        existing.label = `${existing.label} (ميزان)`;
                        return;
                    }
                    floorOptions.push({ value: floorNumber, label: `${floorNumber} (ميزان)` });
                });
            }
            floorOptions.sort((a, b) => Number(a.value) - Number(b.value));

            floorOptions.forEach((item) => {
                const option = document.createElement('option');
                option.value = String(item.value);
                option.textContent = item.label;
                if (item.value === selectedFloor) option.selected = true;
                floorSelect.appendChild(option);
            });

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

        propertySelect?.addEventListener('change', refreshPropertyMeta);
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
