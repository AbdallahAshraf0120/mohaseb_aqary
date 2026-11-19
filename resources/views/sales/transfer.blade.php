@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4" style="max-width: 820px">
        <div class="card-body p-4">
            <h5 class="fw-semibold mb-1">تنازل عن {{ $sale->property?->name }} — {{ $sale->unitLabel() }}</h5>
            <div class="text-body-secondary small mb-4">العميل الحالي: <b>{{ $sale->client?->name }}</b> ({{ $sale->client?->phone }}) · SL-{{ $sale->id }}</div>

            @if ($sale->transfers->isNotEmpty())
                <div class="small mb-3">
                    <div class="fw-semibold mb-1">تنازلات سابقة:</div>
                    @foreach ($sale->transfers as $t)
                        <div>{{ $t->transferred_at->format('Y-m-d') }}: {{ $t->fromClient?->name }} ← {{ $t->toClient?->name }}</div>
                    @endforeach
                </div>
            @endif

            <form method="post" action="{{ route('sales.transfer', [$project, $sale]) }}" onsubmit="return confirm('تأكيد التنازل؟')">
                @csrf
                @if ($existingClients->isNotEmpty())
                    <div class="mb-3">
                        <label class="form-label">عميل موجود؟</label>
                        <select id="existing-client" class="form-select" data-tomselect data-placeholder="ابحث…">
                            <option value="">— عميل جديد —</option>
                            @foreach ($existingClients as $ec)
                                <option value="{{ $ec->id }}" data-name="{{ $ec->name }}" data-phone="{{ $ec->phone }}" data-nid="{{ $ec->national_id }}">{{ $ec->name }} — {{ $ec->phone }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="row g-3 mb-3">
                    <div class="col-md-4"><label class="form-label">اسم المتنازَل له</label><input name="client_name" class="form-control" value="{{ old('client_name') }}" required></div>
                    <div class="col-md-4"><label class="form-label">الموبايل</label><input name="client_phone" class="form-control" value="{{ old('client_phone') }}" required></div>
                    <div class="col-md-4"><label class="form-label">الرقم القومي</label><input name="client_national_id" class="form-control" value="{{ old('client_national_id') }}"></div>
                    <div class="col-md-4"><label class="form-label">رسوم التنازل (اختياري)</label><input type="number" step="0.01" min="0" name="fee" class="form-control" value="{{ old('fee', 0) }}"></div>
                    <div class="col-md-4"><label class="form-label">تاريخ التنازل</label><input type="date" name="transferred_at" class="form-control" value="{{ old('transferred_at', now()->toDateString()) }}" required></div>
                    <div class="col-md-4"><label class="form-label">ملاحظات</label><input name="notes" class="form-control" value="{{ old('notes') }}"></div>
                </div>
                <div class="alert alert-info small">العقد والأقساط الباقية بيتنقلوا للعميل الجديد بنفس الجدول. رسوم التنازل بتدخل الصندوق كإيراد للمشروع.</div>
                <div class="d-flex gap-2">
                    <a href="{{ route('sales.show', [$project, $sale]) }}" class="btn btn-outline-secondary">رجوع</a>
                    <button class="btn btn-primary">تأكيد التنازل</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var sel = document.getElementById('existing-client'); if (!sel) return;
            sel.addEventListener('change', function () {
                var o = sel.options[sel.selectedIndex]; if (!o || !o.value) return;
                document.querySelector('[name=client_name]').value = o.dataset.name || '';
                document.querySelector('[name=client_phone]').value = o.dataset.phone || '';
                document.querySelector('[name=client_national_id]').value = o.dataset.nid || '';
            });
        });
    </script>
@endsection
