@extends('layouts.admin')

@section('content')
    @php
        $meta = $properties->mapWithKeys(fn ($p) => [(string) $p->id => [
            'floors' => collect($p->registered_floors ?? [])->map(fn ($v) => (int) $v)->filter()->values()->all() ?: range(1, max(1, (int) $p->floors_count)),
            'shops' => (int) ($p->ground_floor_shops_count ?? 0),
            'per_floor' => (int) ($p->apartments_per_floor ?? 0),
            'standalone' => $p->isStandaloneUnits(),
            'word' => $p->unitWord(),
        ]]);
    @endphp
    <div class="card app-surface mb-4" style="max-width: 900px">
        <div class="card-body p-4">
            <form method="post" action="{{ route('reservations.store', $project) }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">العقار</label>
                        <select name="property_id" id="r-prop" class="form-select" required>
                            <option value="">اختر…</option>
                            @foreach ($properties as $p)<option value="{{ $p->id }}" @selected(old('property_id') == $p->id)>{{ $p->name }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3" id="r-floor-col">
                        <label class="form-label">الدور</label>
                        <select name="floor_number" id="r-floor" class="form-select" required></select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" id="r-unit-label">رقم الوحدة</label>
                        <select name="unit_number" id="r-unit" class="form-select" required></select>
                    </div>

                    @if ($existingClients->isNotEmpty())
                        <div class="col-12">
                            <label class="form-label">عميل موجود؟</label>
                            <select id="existing-client" class="form-select" data-tomselect data-placeholder="ابحث…">
                                <option value="">— عميل جديد —</option>
                                @foreach ($existingClients as $ec)
                                    <option value="{{ $ec->id }}" data-name="{{ $ec->name }}" data-phone="{{ $ec->phone }}" data-nid="{{ $ec->national_id }}">{{ $ec->name }} — {{ $ec->phone }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-4"><label class="form-label">اسم العميل</label><input name="client_name" class="form-control" value="{{ old('client_name') }}" required></div>
                    <div class="col-md-4"><label class="form-label">الموبايل</label><input name="client_phone" class="form-control" value="{{ old('client_phone') }}" required></div>
                    <div class="col-md-4"><label class="form-label">الرقم القومي</label><input name="client_national_id" class="form-control" value="{{ old('client_national_id') }}"></div>

                    <div class="col-md-4"><label class="form-label">العربون</label><input type="number" step="0.01" min="0" name="deposit" class="form-control" value="{{ old('deposit', 0) }}" required></div>
                    <div class="col-md-4"><label class="form-label">تاريخ الحجز</label><input type="date" name="reserved_at" class="form-control" value="{{ old('reserved_at', now()->toDateString()) }}" required></div>
                    <div class="col-md-4"><label class="form-label">الحجز ساري لحد</label><input type="date" name="expires_at" class="form-control" value="{{ old('expires_at', now()->addDays(7)->toDateString()) }}" required></div>
                    <div class="col-12"><label class="form-label">ملاحظات</label><input name="notes" class="form-control" value="{{ old('notes') }}"></div>
                </div>
                <div class="alert alert-info small mt-3">الوحدة بتتقفل لحد تاريخ انتهاء الحجز. العربون بيدخل الصندوق كأمانة، ولما الحجز يتحوّل لبيعة بيتحسب من المقدم.</div>
                <div class="d-flex gap-2">
                    <a href="{{ route('reservations.index', $project) }}" class="btn btn-outline-secondary">رجوع</a>
                    <button class="btn btn-primary">احجز الوحدة</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        (function () {
            const meta = @json($meta), taken = @json($takenUnits);
            const prop = document.getElementById('r-prop'), floor = document.getElementById('r-floor'), unit = document.getElementById('r-unit');
            function units() {
                const m = meta[prop.value] || {}; const f = Number(floor.value || 1);
                const count = m.standalone ? m.per_floor : (f === 0 ? m.shops : m.per_floor);
                const t = ((taken[prop.value] || {})[f + ':0'] || {units: []});
                const set = new Set((t.units || []).map(Number)); const res = new Set((t.reserved || []).map(Number));
                const word = m.standalone ? m.word : (f === 0 ? 'محل' : 'شقة');
                unit.innerHTML = '';
                for (let n = 1; n <= Math.max(1, count); n++) {
                    const o = document.createElement('option'); o.value = n;
                    o.textContent = word + ' ' + n + (set.has(n) ? (res.has(n) ? ' — محجوزة' : ' — مبيعة') : '');
                    o.disabled = set.has(n); unit.appendChild(o);
                }
                const free = [...unit.options].find((o) => !o.disabled); if (free) unit.value = free.value;
                document.getElementById('r-unit-label').textContent = 'رقم ال' + word;
            }
            function floors() {
                const m = meta[prop.value] || {floors: [1], shops: 0};
                document.getElementById('r-floor-col').style.display = m.standalone ? 'none' : '';
                floor.innerHTML = '';
                const list = m.standalone ? [1] : [...(m.shops > 0 ? [0] : []), ...m.floors];
                list.forEach((n) => { const o = document.createElement('option'); o.value = n; o.textContent = n === 0 ? 'أرضي (محلات)' : n; floor.appendChild(o); });
                units();
            }
            prop.addEventListener('change', floors); floor.addEventListener('change', units); floors();
            document.addEventListener('DOMContentLoaded', function () {
                const sel = document.getElementById('existing-client'); if (!sel) return;
                sel.addEventListener('change', function () {
                    const o = sel.options[sel.selectedIndex]; if (!o || !o.value) return;
                    document.querySelector('[name=client_name]').value = o.dataset.name || '';
                    document.querySelector('[name=client_phone]').value = o.dataset.phone || '';
                    document.querySelector('[name=client_national_id]').value = o.dataset.nid || '';
                });
            });
        })();
    </script>
@endsection
