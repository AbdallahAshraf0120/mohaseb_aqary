@csrf

<div class="row g-3">
    <div class="col-md-4">
        <label class="form-label">الرمز (إنجليزي)</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', isset($facing) ? $facing->code : '') }}" required
               pattern="[a-z][a-z0-9_]*" maxlength="64" autocomplete="off"
               placeholder="مثال: garden_view">
        <small class="text-muted">حروف إنجليزية صغيرة وأرقام وشرطة سفلية، يبدأ بحرف.</small>
    </div>
    <div class="col-md-5">
        <label class="form-label">الاسم المعروض</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', isset($facing) ? $facing->name : '') }}" required maxlength="255"
               placeholder="مثال: واجهة حديقة">
    </div>
    <div class="col-md-3">
        <label class="form-label">ترتيب العرض</label>
        <input type="number" name="sort_order" class="form-control" min="0" max="999999"
               value="{{ old('sort_order', isset($facing) ? $facing->sort_order : 0) }}">
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
