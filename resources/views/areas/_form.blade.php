@csrf

<div class="mb-3">
    <label class="form-label">اسم المنطقة</label>
    <input type="text" name="name" class="form-control" value="{{ old('name', $area->name ?? '') }}" required>
</div>

@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif
