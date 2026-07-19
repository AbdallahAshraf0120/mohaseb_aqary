@csrf

@php
    $isEdit = isset($shareholder) && $shareholder->exists;
    $linkType = old('link_type', 'project');
    $landsReady = ! empty($landsReady) && ($lands ?? collect())->isNotEmpty();
@endphp

<div class="row g-3" id="shareholder-form-root">
    <div class="col-md-6">
        <label class="form-label">اسم المساهم</label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $shareholder->name ?? '') }}" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    @if (! $isEdit)
        <div class="col-md-6">
            <label class="form-label">نوع الربط الأول</label>
            <div class="d-flex gap-3 flex-wrap pt-1">
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="link_type" id="link-type-project" value="project"
                           @checked($linkType === 'project') required>
                    <label class="form-check-label" for="link-type-project">مشروع</label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="link_type" id="link-type-land" value="land"
                           @checked($linkType === 'land')
                           @disabled(! $landsReady)>
                    <label class="form-check-label" for="link-type-land">أرض بيع/شراء</label>
                </div>
            </div>
            @error('link_type') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
            @unless ($landsReady)
                <div class="form-text">لا توجد أراضي بسعر شراء بعد — أضف أرضًا من «أراضي البيع والشراء» أولًا.</div>
            @endunless
        </div>

        <div class="col-md-6" id="shareholder-project-wrap">
            <label class="form-label">المشروع الأول</label>
            <select name="project_id" id="shareholder-project-id" class="form-select @error('project_id') is-invalid @enderror">
                <option value="" data-capital="0">اختر المشروع…</option>
                @foreach ($projects ?? [] as $p)
                    @php $cap = (float) ($p->planned_capital ?? $p->capital ?? 0); @endphp
                    <option value="{{ $p->id }}"
                            data-capital="{{ $cap }}"
                            @selected((string) old('project_id', request('project_id')) === (string) $p->id)>
                        {{ $p->name }}
                        @if ($cap > 0)
                            — رأس المال: {{ number_format($cap, 2) }} ج.م
                        @endif
                    </option>
                @endforeach
            </select>
            @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text" id="shareholder-project-capital-hint">أدخل النسبة المخططة — التمويل النقدي اختياري.</div>
        </div>

        <div class="col-md-6 d-none" id="shareholder-land-wrap">
            <label class="form-label">الأرض الأولى</label>
            <select name="land_parcel_id" id="shareholder-land-id" class="form-select @error('land_parcel_id') is-invalid @enderror">
                <option value="" data-capital="0">اختر الأرض…</option>
                @foreach ($lands ?? [] as $land)
                    @php $lcap = (float) ($land->planned_capital ?? $land->purchase_price ?? 0); @endphp
                    <option value="{{ $land->id }}"
                            data-capital="{{ $lcap }}"
                            @selected((string) old('land_parcel_id') === (string) $land->id)>
                        {{ $land->name }}
                        @if ($lcap > 0)
                            — شراء: {{ number_format($lcap, 2) }} ج.م
                        @endif
                    </option>
                @endforeach
            </select>
            @error('land_parcel_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text" id="shareholder-land-capital-hint">أدخل النسبة المخططة — التمويل النقدي اختياري ويُسجَّل لاحقًا بالسداد.</div>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="shareholder-share-percentage">نسبة المساهمة (%)</label>
            <input id="shareholder-share-percentage" type="number" step="0.01" min="0.01" max="100" name="share_percentage"
                   class="form-control font-monospace @error('share_percentage') is-invalid @enderror"
                   value="{{ old('share_percentage') }}" required>
            @error('share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text" id="shareholder-pct-hint">إلزامي — النسبة المخططة من البداية.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="shareholder-total-investment" id="shareholder-investment-label">التمويل النقدي (اختياري)</label>
            <input id="shareholder-total-investment" type="number" step="0.01" min="0" name="total_investment"
                   class="form-control font-monospace @error('total_investment') is-invalid @enderror"
                   value="{{ old('total_investment') }}">
            @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text" id="shareholder-investment-hint">لو فاضي: النسبة فقط بدون إيداع في الجاري.</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="alert alert-light border small mb-0 w-100 py-2">
                <span class="font-monospace" id="shareholder-pct-formula">—</span>
            </div>
        </div>
    @else
        <div class="col-md-6 d-flex align-items-end">
            <div class="alert alert-light border small mb-0 w-100">
                لربط المساهم بمشروع أو أرض إضافية أو تسجيل حركات الجاري، افتح <strong>البروفايل</strong>.
            </div>
        </div>
    @endif
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

@if (! $isEdit)
<script>
(function () {
    const investmentInput = document.getElementById('shareholder-total-investment');
    const pctInput = document.getElementById('shareholder-share-percentage');
    const formulaEl = document.getElementById('shareholder-pct-formula');
    const projectSelect = document.getElementById('shareholder-project-id');
    const landSelect = document.getElementById('shareholder-land-id');
    const projectWrap = document.getElementById('shareholder-project-wrap');
    const landWrap = document.getElementById('shareholder-land-wrap');
    const projectHint = document.getElementById('shareholder-project-capital-hint');
    const landHint = document.getElementById('shareholder-land-capital-hint');
    const radios = document.querySelectorAll('input[name="link_type"]');

    function linkType() {
        const checked = document.querySelector('input[name="link_type"]:checked');
        return checked ? checked.value : 'project';
    }
    function activeSelect() {
        return linkType() === 'land' ? landSelect : projectSelect;
    }
    function capital() {
        const sel = activeSelect();
        const opt = sel?.options[sel.selectedIndex];
        return parseFloat(opt?.dataset?.capital || '0') || 0;
    }
    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function syncVisibility() {
        const isLand = linkType() === 'land';
        projectWrap?.classList.toggle('d-none', isLand);
        landWrap?.classList.toggle('d-none', !isLand);
        if (projectSelect) projectSelect.required = !isLand;
        if (landSelect) landSelect.required = isLand;
        recalc();
    }
    function recalc() {
        const isLand = linkType() === 'land';
        const cap = capital();
        const pct = parseFloat(pctInput?.value || '0') || 0;
        const planned = cap > 0 ? Math.round(cap * (pct / 100) * 100) / 100 : 0;
        if (formulaEl) {
            formulaEl.textContent = cap <= 0
                ? (isLand ? 'عيّن سعر شراء الأرض أولاً' : 'عيّن رأس مال المشروع أولاً')
                : 'تمويل مخطط ≈ ' + formatMoney(planned) + ' ج.م (' + pct.toFixed(2) + '% من ' + formatMoney(cap) + ')';
        }
        if (!isLand && projectHint) {
            projectHint.textContent = cap > 0
                ? ('رأس مال المشروع: ' + formatMoney(cap) + ' ج.م')
                : 'هذا المشروع بلا رأس مال. عيّنه من صفحة المشاريع أولاً.';
        }
        if (isLand && landHint) {
            landHint.textContent = cap > 0
                ? ('سعر شراء الأرض: ' + formatMoney(cap) + ' ج.م')
                : 'هذه الأرض بلا سعر شراء. عيّنه من صفحة الأرض أولاً.';
        }
    }
    radios.forEach((r) => r.addEventListener('change', syncVisibility));
    pctInput?.addEventListener('input', recalc);
    projectSelect?.addEventListener('change', recalc);
    landSelect?.addEventListener('change', recalc);
    syncVisibility();
})();
</script>
@endif
