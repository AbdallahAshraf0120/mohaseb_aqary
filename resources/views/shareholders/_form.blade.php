@csrf

@php
    $isEdit = isset($shareholder) && $shareholder->exists;
    $projectCapital = $isEdit
        ? (float) ($shareholder->project?->capital ?? 0)
        : 0;
@endphp

<div class="row g-3" id="shareholder-form-root"
     data-project-capital="{{ $projectCapital }}">
    @if (! $isEdit)
        <div class="col-md-6">
            <label class="form-label">المشروع</label>
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
            <div class="form-text" id="shareholder-project-capital-hint">النسبة تُحسب تلقائيًا من تمويل المساهم ÷ رأس مال المشروع.</div>
        </div>
    @else
        <div class="col-md-6">
            <label class="form-label">المشروع</label>
            <input type="text" class="form-control" value="{{ $shareholder->project?->name ?? '—' }}" disabled>
            <div class="form-text">
                رأس مال المشروع:
                <span class="font-monospace fw-semibold" id="shareholder-project-capital-label">{{ number_format($projectCapital, 2) }}</span> ج.م
                — لا يمكن نقل المساهم لمشروع آخر من هنا.
            </div>
        </div>
    @endif
    <div class="col-md-6">
        <label class="form-label">اسم المساهم</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $shareholder->name ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label" for="shareholder-total-investment">رأس المال / التمويل المُدخل (ج.م)</label>
        <input id="shareholder-total-investment" type="number" step="0.01" min="0.01" name="total_investment"
               class="form-control font-monospace @error('total_investment') is-invalid @enderror"
               value="{{ old('total_investment', $shareholder->total_investment ?? '') }}" required>
        @error('total_investment') <div class="invalid-feedback">{{ $message }}</div> @enderror
        @if (! $isEdit)
            <div class="form-text">عند الإنشاء يُسجَّل كحركة <strong>إيداع رأس مال</strong> في الجاري ويُربط بإيراد في الصندوق.</div>
        @else
            <div class="form-text">للتعديل الإداري. زيادة/نقص رأس المال الفعلية تتم أيضًا عبر حركات الجاري في البروفايل.</div>
        @endif
    </div>
    <div class="col-md-4">
        <label class="form-label">نسبة المساهمة (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="share_percentage" id="shareholder-share-percentage"
               class="form-control @error('share_percentage') is-invalid @enderror"
               value="{{ old('share_percentage', $shareholder->share_percentage ?? '') }}"
               readonly required>
        @error('share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">محسوبة تلقائيًا: التمويل ÷ رأس مال المشروع × 100.</div>
    </div>
    <div class="col-md-4 d-flex align-items-end">
        <div class="alert alert-light border small mb-0 w-100 py-2">
            <span class="text-body-secondary">المعادلة:</span>
            <span class="font-monospace" id="shareholder-pct-formula">—</span>
        </div>
    </div>
    <div class="col-12">
        <div class="alert alert-light border small mb-0">
            <i class="fa-solid fa-book text-primary ms-1"></i>
            <strong>جاري المساهم (دفتر):</strong> حركات يدوية (إيداع / سحب / توزيع / تصفية / تسوية). الإيداع والسحب والتوزيع والتصفية تؤثر على الصندوق.
            <br>
            <strong>المرجع المحسوب:</strong> المنسب وحصة التكاليف من العقارات تبقى للعرض فقط ولا تدخل الدفتر تلقائياً.
        </div>
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
    const root = document.getElementById('shareholder-form-root');
    if (!root) return;

    const investmentInput = document.getElementById('shareholder-total-investment');
    const pctInput = document.getElementById('shareholder-share-percentage');
    const formulaEl = document.getElementById('shareholder-pct-formula');
    const projectSelect = document.getElementById('shareholder-project-id');
    const hintEl = document.getElementById('shareholder-project-capital-hint');

    function projectCapital() {
        if (projectSelect) {
            const opt = projectSelect.options[projectSelect.selectedIndex];
            return parseFloat(opt?.dataset?.capital || '0') || 0;
        }
        return parseFloat(root.dataset.projectCapital || '0') || 0;
    }

    function formatMoney(n) {
        return (Math.round(n * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function recalc() {
        const capital = projectCapital();
        const investment = parseFloat(investmentInput?.value || '0') || 0;
        let pct = 0;
        if (capital > 0 && investment >= 0) {
            pct = Math.round((investment / capital) * 10000) / 100;
        }
        if (pctInput) pctInput.value = capital > 0 ? pct.toFixed(2) : '';
        if (formulaEl) {
            if (capital <= 0) {
                formulaEl.textContent = 'عيّن رأس مال المشروع أولاً';
            } else {
                formulaEl.textContent = formatMoney(investment) + ' ÷ ' + formatMoney(capital) + ' × 100 = ' + pct.toFixed(2) + '%';
            }
        }
        if (hintEl && projectSelect) {
            hintEl.textContent = capital > 0
                ? ('رأس مال المشروع المختار: ' + formatMoney(capital) + ' ج.م — النسبة = التمويل ÷ رأس المال × 100')
                : 'هذا المشروع بلا رأس مال. عيّنه من صفحة المشاريع أولاً.';
        }
    }

    investmentInput?.addEventListener('input', recalc);
    projectSelect?.addEventListener('change', recalc);
    recalc();
})();
</script>
