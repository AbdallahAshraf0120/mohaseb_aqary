@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم الأرض</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $land->name ?? '') }}" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">المنطقة</label>
        <select name="area_id" class="form-select">
            <option value="">اختر المنطقة</option>
            @foreach ($areas as $area)
                <option value="{{ $area->id }}" @selected((string) old('area_id', $land->area_id ?? '') === (string) $area->id)>{{ $area->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">تكلفة الأرض</label>
        <input type="number" step="0.01" min="0" name="land_cost" class="form-control" value="{{ old('land_cost', $land->land_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">رخصة البناء</label>
        <input type="number" step="0.01" min="0" name="building_license_cost" class="form-control" value="{{ old('building_license_cost', $land->building_license_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">خوازيق</label>
        <input type="number" step="0.01" min="0" name="piles_cost" class="form-control" value="{{ old('piles_cost', $land->piles_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">حفر</label>
        <input type="number" step="0.01" min="0" name="excavation_cost" class="form-control" value="{{ old('excavation_cost', $land->excavation_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">ظلط</label>
        <input type="number" step="0.01" min="0" name="gravel_cost" class="form-control" value="{{ old('gravel_cost', $land->gravel_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">رملة</label>
        <input type="number" step="0.01" min="0" name="sand_cost" class="form-control" value="{{ old('sand_cost', $land->sand_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">أسمنت</label>
        <input type="number" step="0.01" min="0" name="cement_cost" class="form-control" value="{{ old('cement_cost', $land->cement_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">حديد</label>
        <input type="number" step="0.01" min="0" name="steel_cost" class="form-control" value="{{ old('steel_cost', $land->steel_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">عمالة نجارة</label>
        <input type="number" step="0.01" min="0" name="carpentry_labor_cost" class="form-control" value="{{ old('carpentry_labor_cost', $land->carpentry_labor_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">عمالة حدادة</label>
        <input type="number" step="0.01" min="0" name="blacksmith_labor_cost" class="form-control" value="{{ old('blacksmith_labor_cost', $land->blacksmith_labor_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">عمالة بناَّء</label>
        <input type="number" step="0.01" min="0" name="mason_labor_cost" class="form-control" value="{{ old('mason_labor_cost', $land->mason_labor_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">عمالة كهربائي</label>
        <input type="number" step="0.01" min="0" name="electrician_labor_cost" class="form-control" value="{{ old('electrician_labor_cost', $land->electrician_labor_cost ?? 0) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">إكراميات</label>
        <input type="number" step="0.01" min="0" name="tips_cost" class="form-control" value="{{ old('tips_cost', $land->tips_cost ?? 0) }}">
    </div>
    <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" class="form-control" rows="3">{{ old('notes', $land->notes ?? '') }}</textarea>
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
