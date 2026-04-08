@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم العقار</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $property->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">الموقع</label>
        <input type="text" name="location" class="form-control" value="{{ old('location', $property->location ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">السعر</label>
        <input type="number" step="0.01" name="price" class="form-control" value="{{ old('price', $property->price ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select" required>
            @foreach (['available' => 'متاح', 'reserved' => 'محجوز', 'sold' => 'مباع', 'rented' => 'مؤجر'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $property->status ?? 'available') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">المالك</label>
        <select name="owner_id" class="form-select" required>
            <option value="">اختر المالك</option>
            @foreach ($owners as $owner)
                <option value="{{ $owner->id }}" @selected((string) old('owner_id', $property->owner_id ?? '') === (string) $owner->id)>{{ $owner->name }}</option>
            @endforeach
        </select>
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
