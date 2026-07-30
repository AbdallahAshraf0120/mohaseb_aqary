@csrf
@php
    $selectedTypeId = old('expense_type_id', $expense->expense_type_id ?? '');
@endphp
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">جهة الصرف</label>
        <select name="expense_type_id" class="form-select" required>
            <option value="">اختر جهة الصرف…</option>
            @foreach (($expenseTypes ?? []) as $type)
                <option value="{{ $type->id }}" @selected((string) $selectedTypeId === (string) $type->id)>{{ $type->name }}</option>
            @endforeach
        </select>
        <div class="form-text">
            <a href="{{ route('expense-types.index') }}">إدارة أنواع المصروفات</a>
        </div>
    </div>
    <div class="col-md-3">
        <label class="form-label">القيمة</label>
        <input type="number" step="0.00001" min="1" name="amount" class="form-control" required value="{{ old('amount', $expense->amount ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">تاريخ الصرف</label>
        <input type="date" name="spent_at" class="form-control" required value="{{ old('spent_at', isset($expense) && $expense->spent_at ? $expense->spent_at->format('Y-m-d') : now()->toDateString()) }}">
        <div class="form-text">تاريخ حدوث الصرف فعليًا.</div>
    </div>
    <div class="col-md-3">
        <label class="form-label">الوصف</label>
        <input name="description" class="form-control" value="{{ old('description', $expense->description ?? '') }}">
    </div>
</div>
@if (isset($expense) && $expense->exists)
    <div class="small text-body-secondary mt-3">
        تاريخ الإدخال على النظام:
        <span class="font-monospace">{{ $expense->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
        <span class="opacity-75">(يُسجَّل تلقائيًا ولا يُعدَّل)</span>
    </div>
@else
    <div class="small text-body-secondary mt-3">
        تاريخ الإدخال على النظام يُسجَّل تلقائيًا عند الحفظ.
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
