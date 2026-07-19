@csrf

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label">جهة الصرف</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', isset($expenseType) ? $expenseType->name : '') }}" required maxlength="255"
               placeholder="مثال: مقاولات، كهرباء، رواتب…">
    </div>
    <div class="col-md-4">
        <label class="form-label">ترتيب العرض</label>
        <input type="number" name="sort_order" class="form-control" min="0" max="999999"
               value="{{ old('sort_order', isset($expenseType) ? $expenseType->sort_order : 0) }}">
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
