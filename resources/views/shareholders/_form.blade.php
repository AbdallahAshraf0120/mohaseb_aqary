@csrf

<div class="row g-3">
    @if (! isset($shareholder) || ! $shareholder->exists)
        <div class="col-md-6">
            <label class="form-label">المشروع</label>
            <select name="project_id" class="form-select @error('project_id') is-invalid @enderror" required>
                <option value="">اختر المشروع…</option>
                @foreach ($projects ?? [] as $p)
                    <option value="{{ $p->id }}" @selected((string) old('project_id', request('project_id')) === (string) $p->id)>{{ $p->name }}</option>
                @endforeach
            </select>
            @error('project_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    @else
        <div class="col-md-6">
            <label class="form-label">المشروع</label>
            <input type="text" class="form-control" value="{{ $shareholder->project?->name ?? '—' }}" disabled>
            <div class="form-text">لا يمكن نقل المساهم لمشروع آخر من هنا.</div>
        </div>
    @endif
    <div class="col-md-6">
        <label class="form-label">اسم المساهم</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $shareholder->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">نسبة المساهمة (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="share_percentage" class="form-control @error('share_percentage') is-invalid @enderror"
               value="{{ old('share_percentage', $shareholder->share_percentage ?? '') }}" required>
        @error('share_percentage') <div class="invalid-feedback">{{ $message }}</div> @enderror
        <div class="form-text">مجموع نسب المساهمين في نفس المشروع يجب ألا يتجاوز 100%.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="shareholder-total-investment">رأس المال / التمويل المُدخل (ج.م)</label>
        <input id="shareholder-total-investment" type="number" step="0.01" min="0" name="total_investment" class="form-control font-monospace"
               value="{{ old('total_investment', $shareholder->total_investment ?? '') }}" required>
        @if (! isset($shareholder) || ! $shareholder->exists)
            <div class="form-text">عند الإنشاء يُسجَّل كحركة <strong>إيداع رأس مال</strong> في الجاري ويُربط بإيراد في الصندوق.</div>
        @else
            <div class="form-text">للتعديل الإداري فقط. زيادة/نقص رأس المال تتم عبر حركات الجاري في البروفايل (لا يُحدَّث الدفتر من هذا الحقل).</div>
        @endif
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
