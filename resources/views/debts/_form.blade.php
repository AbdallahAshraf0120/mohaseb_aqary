@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="debt-creditor">اسم المورد / الجهة الدائنة</label>
        <input id="debt-creditor" type="text" name="creditor_name" class="form-control" required
               value="{{ old('creditor_name', $debt->creditor_name ?? '') }}"
               placeholder="مثال: شركة الخرسانة الجاهزة">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="debt-total">إجمالي قيمة الشراء (ج.م)</label>
        <input id="debt-total" type="number" step="0.01" min="0.01" name="total_amount" class="form-control font-monospace" required
               value="{{ old('total_amount', $debt->total_amount ?? '') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label" for="debt-paid">ما سُدِّد حتى الآن (ج.م)</label>
        <input id="debt-paid" type="number" step="0.01" min="0" name="paid_amount" class="form-control font-monospace"
               value="{{ old('paid_amount', isset($debt) ? $debt->paid_amount : 0) }}">
        <div class="form-text">يُحدَّث المتبقي والحالة (مفتوح/مغلق) تلقائياً عند الحفظ.</div>
    </div>
    <div class="col-12">
        <label class="form-label" for="debt-desc">وصف الشراء (اختياري)</label>
        <textarea id="debt-desc" name="purchase_description" class="form-control" rows="3"
                  placeholder="مثال: توريد حديد تسليح — دفعة أولى">{{ old('purchase_description', $debt->purchase_description ?? '') }}</textarea>
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
