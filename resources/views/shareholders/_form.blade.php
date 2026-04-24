@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم المساهم</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $shareholder->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">نسبة المساهمة (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="share_percentage" class="form-control"
               value="{{ old('share_percentage', $shareholder->share_percentage ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label" for="shareholder-total-investment">رأس المال / التمويل المُدخل (ج.م)</label>
        <input id="shareholder-total-investment" type="number" step="0.01" min="0" name="total_investment" class="form-control font-monospace"
               value="{{ old('total_investment', $shareholder->total_investment ?? '') }}" required>
        <div class="form-text">يُحدَّد في <strong>لحظة التسجيل</strong> أو عند <strong>أي تعديل</strong> لاحق — سجل إداري لما اعتمدتموه كتمويل للمساهم.</div>
    </div>
    <div class="col-12">
        <div class="alert alert-light border small mb-0">
            <i class="fa-solid fa-calculator text-primary ms-1"></i>
            <strong>لكل مساهم على حدة:</strong> يُحسب <strong>حصة التكاليف</strong> من حقول تكلفة كل عقار × نسبة المساهم في التوزيع، و<strong>المنسب التشغيلي</strong> من التحصيلات ومقدمات البيع على العقار بنفس النسبة، و<strong>الجاري (تقريبي)</strong> = المنسب − حصة التكلفة — يظهر في القائمة والبروفايل دون تكرار لكل المساهمين في حساب واحد.
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
