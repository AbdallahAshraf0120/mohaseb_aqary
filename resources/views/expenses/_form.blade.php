@csrf
<div class="row g-3">
    <div class="col-md-3">
        <label class="form-label">الفئة</label>
        <input name="category" class="form-control" required value="{{ old('category', $expense->category ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">القيمة</label>
        <input type="number" step="0.01" min="1" name="amount" class="form-control" required value="{{ old('amount', $expense->amount ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">تاريخ الصرف</label>
        <input type="date" name="spent_at" class="form-control" required value="{{ old('spent_at', isset($expense) && $expense->spent_at ? $expense->spent_at->format('Y-m-d') : now()->toDateString()) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">الوصف</label>
        <input name="description" class="form-control" value="{{ old('description', $expense->description ?? '') }}">
    </div>
</div>
@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
