@csrf

@php
    $isEdit = isset($shareholder) && $shareholder->exists;
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
            <label class="form-label">المشروع الأول</label>
            <select name="project_id" id="shareholder-project-id" class="form-select @error('project_id') is-invalid @enderror" required>
                <option value="" data-capital="0">اختر المشروع…</option>
                @foreach ($projects ?? [] as $p)
                    <option value="{{ $p->id }}"
                            data-capital="{{ (float) ($p->capital ?? 0) }}"
                            @selected((string) old('project_id', request('project_id')) === (string) $p->id)>
                        {{ $p->name }}
                        @if ((float) ($p->capital ?? 0) > 0)
                            — رأس المال: {{ number_format((float) $p->capital, 2) }} ج.م
                        @endif
                    </option>
                @endforeach
            </select>
            @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text" id="shareholder-project-capital-hint">يمكن لاحقًا ربط نفس المساهم بمشاريع أخرى من البروفايل.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="shareholder-total-investment">التمويل في هذا المشروع (ج.م)</label>
            <input id="shareholder-total-investment" type="number" step="0.01" min="0.01" name="total_investment"
                   class="form-control font-monospace @error('total_investment') is-invalid @enderror"
                   value="{{ old('total_investment') }}" required>
            @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
            <div class="form-text">يُسجَّل كإيداع رأس مال في الجاري لصندوق هذا المشروع.</div>
        </div>
        <div class="col-md-4">
            <label class="form-label">نسبة المساهمة (%)</label>
            <input type="text" id="shareholder-share-percentage" class="form-control" value="" readonly>
            <div class="form-text">محسوبة: التمويل ÷ رأس مال المشروع × 100.</div>
        </div>
        <div class="col-md-4 d-flex align-items-end">
            <div class="alert alert-light border small mb-0 w-100 py-2">
                <span class="font-monospace" id="shareholder-pct-formula">—</span>
            </div>
        </div>
    @else
        <div class="col-md-6 d-flex align-items-end">
            <div class="alert alert-light border small mb-0 w-100">
                لربط المساهم بمشروع إضافي أو تسجيل حركات الجاري، افتح <strong>البروفايل</strong>.
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
    const hintEl = document.getElementById('shareholder-project-capital-hint');

    function projectCapital() {
        const opt = projectSelect?.options[projectSelect.selectedIndex];
        return parseFloat(opt?.dataset?.capital || '0') || 0;
    }
    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    function recalc() {
        const capital = projectCapital();
        const investment = parseFloat(investmentInput?.value || '0') || 0;
        let pct = 0;
        if (capital > 0) pct = Math.round((investment / capital) * 10000) / 100;
        if (pctInput) pctInput.value = capital > 0 ? pct.toFixed(2) : '';
        if (formulaEl) {
            formulaEl.textContent = capital <= 0
                ? 'عيّن رأس مال المشروع أولاً'
                : formatMoney(investment) + ' ÷ ' + formatMoney(capital) + ' × 100 = ' + pct.toFixed(2) + '%';
        }
        if (hintEl) {
            hintEl.textContent = capital > 0
                ? ('رأس مال المشروع: ' + formatMoney(capital) + ' ج.م — يمكن لاحقًا إضافة مشاريع أخرى من البروفايل')
                : 'هذا المشروع بلا رأس مال. عيّنه من صفحة المشاريع أولاً.';
        }
    }
    investmentInput?.addEventListener('input', recalc);
    projectSelect?.addEventListener('change', recalc);
    recalc();
})();
</script>
@endif
