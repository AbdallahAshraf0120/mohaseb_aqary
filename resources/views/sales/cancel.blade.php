@extends('layouts.admin')

@section('content')
    @php $m = fn ($v) => \App\Support\Money::display((float) $v); @endphp
    <div class="card app-surface mb-4" style="max-width: 760px">
        <div class="card-body p-4">
            <h5 class="fw-semibold mb-1">إلغاء تعاقد: {{ $sale->client?->name }}</h5>
            <div class="text-body-secondary small mb-4">{{ $sale->property?->name }} — {{ $sale->unitLabel() }} · SL-{{ $sale->id }}</div>

            <div class="row g-3 mb-4">
                <div class="col-sm-4"><div class="border rounded-3 p-3"><div class="small text-body-secondary">سعر البيع</div><div class="fw-bold font-monospace">{{ $m($sale->sale_price) }}</div></div></div>
                <div class="col-sm-4"><div class="border rounded-3 p-3"><div class="small text-body-secondary">العميل دفع</div><div class="fw-bold font-monospace text-success-emphasis">{{ $m($paid) }}</div></div></div>
                <div class="col-sm-4"><div class="border rounded-3 p-3"><div class="small text-body-secondary">رصيد الصندوق</div><div class="fw-bold font-monospace">{{ $m($balance) }}</div></div></div>
            </div>

            <form method="post" action="{{ route('sales.cancel', [$project, $sale]) }}" onsubmit="return confirm('تأكيد إلغاء التعاقد؟ الوحدة هترجع متاحة والخطوة دي ما ترجعش.')">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold">المبلغ اللي هيترد للعميل</label>
                    <div class="input-group" style="max-width: 22rem">
                        <input type="number" step="0.01" min="0" max="{{ $paid }}" name="refund_amount" id="refund" class="form-control font-monospace" value="{{ old('refund_amount', $paid) }}" required>
                        <span class="input-group-text">ج.م</span>
                    </div>
                    <div class="form-text">اكتب أقل من المدفوع لو فيه غرامة/خصم حسب العقد. المحتجز: <b id="kept" class="font-monospace">0</b> ج.م وبيتحسب إيراد للمشروع.</div>
                    <div class="d-flex gap-2 mt-2">
                        @foreach ([100, 90, 75, 50, 0] as $pct)
                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('refund').value=({{ $paid }}*{{ $pct }}/100).toFixed(2);upd()">{{ $pct }}%</button>
                        @endforeach
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold">سبب الإلغاء</label>
                    <input type="text" name="reason" class="form-control" maxlength="500" value="{{ old('reason') }}" required placeholder="مثال: العميل طلب الفسخ / تأخر في السداد">
                </div>
                <div class="alert alert-warning small">
                    الوحدة هترجع متاحة للبيع، والعقد هيطلع من «المتبقي» و«الأقساط المتأخرة». الاسترداد بيخرج من الصندوق، والفلوس اللي العميل دفعها قبل كده بتفضل مسجّلة.
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('sales.show', [$project, $sale]) }}" class="btn btn-outline-secondary">رجوع</a>
                    <button class="btn btn-danger">إلغاء التعاقد</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        function upd() { var p = {{ $paid }}, r = parseFloat(document.getElementById('refund').value || 0); document.getElementById('kept').textContent = Math.max(0, p - r).toLocaleString('en-US', {maximumFractionDigits: 2}); }
        document.getElementById('refund').addEventListener('input', upd); upd();
    </script>
@endsection
