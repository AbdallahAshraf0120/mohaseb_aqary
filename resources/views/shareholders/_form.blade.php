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
            <div class="form-text" id="shareholder-project-capital-hint">يمكن لاحقًا ربط نفس المساهم بمشاريع/أراضي أخرى من البروفايل.</div>
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
            <div class="form-text" id="shareholder-land-capital-hint">النسبة = التمويل ÷ سعر شراء الأرض × 100.</div>
        </div>

        <div class="col-md-4">
            <label class="form-label" for="shareholder-total-investment" id="shareholder-investment-label">التمويل (ج.م)</label>
            <input id="shareholder-total-investment" type="number" step="0.01" min="0.01" name="total_investment"
                   class="form-control font-monospace @error('total_investment') is-invalid @enderror"
                   value="{{ old('total_investment') }}" required>
            @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text" id="shareholder-investment-hint">يُسجَّل كإيداع رأس مال في الجاري.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">نسبة المساهمة (%)</label>
            <input type="text" id="shareholder-share-percentage" class="form-control" value="" readonly>
            <div class="form-text" id="shareholder-pct-hint">محسوبة: التمويل ÷ رأس المال × 100.</div>
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
    const investmentLabel = document.getElementById('shareholder-investment-label');
    const investmentHint = document.getElementById('shareholder-investment-hint');
    const pctHint = document.getElementById('shareholder-pct-hint');
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
        if (investmentLabel) {
            investmentLabel.textContent = isLand ? 'التمويل في هذه الأرض (ج.م)' : 'التمويل في هذا المشروع (ج.م)';
        }
        if (investmentHint) {
            investmentHint.textContent = isLand
                ? 'يُسجَّل كإيداع رأس مال في الجاري مرتبط بهذه الأرض.'
                : 'يُسجَّل كإيداع رأس مال في الجاري لصندوق هذا المشروع.';
        }
        if (pctHint) {
            pctHint.textContent = isLand
                ? 'محسوبة: التمويل ÷ سعر شراء الأرض × 100.'
                : 'محسوبة: التمويل ÷ رأس مال المشروع × 100.';
        }
        recalc();
    }
    function recalc() {
        const isLand = linkType() === 'land';
        const cap = capital();
        const investment = parseFloat(investmentInput?.value || '0') || 0;
        let pct = 0;
        if (cap > 0) pct = Math.round((investment / cap) * 10000) / 100;
        if (pctInput) pctInput.value = cap > 0 ? pct.toFixed(2) : '';
        if (formulaEl) {
            formulaEl.textContent = cap <= 0
                ? (isLand ? 'عيّن سعر شراء الأرض أولاً' : 'عيّن رأس مال المشروع أولاً')
                : formatMoney(investment) + ' ÷ ' + formatMoney(cap) + ' × 100 = ' + pct.toFixed(2) + '%';
        }
        if (!isLand && projectHint) {
            projectHint.textContent = cap > 0
                ? ('رأس مال المشروع: ' + formatMoney(cap) + ' ج.م — يمكن لاحقًا إضافة مشاريع/أراضي من البروفايل')
                : 'هذا المشروع بلا رأس مال. عيّنه من صفحة المشاريع أولاً.';
        }
        if (isLand && landHint) {
            landHint.textContent = cap > 0
                ? ('سعر شراء الأرض: ' + formatMoney(cap) + ' ج.م — يمكن لاحقًا إضافة روابط أخرى من البروفايل')
                : 'هذه الأرض بلا سعر شراء. عيّنه من صفحة الأرض أولاً.';
        }
    }
    radios.forEach((r) => r.addEventListener('change', syncVisibility));
    investmentInput?.addEventListener('input', recalc);
    projectSelect?.addEventListener('change', recalc);
    landSelect?.addEventListener('change', recalc);
    syncVisibility();
})();
</script>
@endif
