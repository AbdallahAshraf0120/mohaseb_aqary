@php
    /** @var \App\Models\LandParcel $parcel */
    $isEdit = $parcel->exists;
@endphp

<div class="row g-3">
    <div class="col-12">
        <h6 class="text-body-secondary mb-0">بيانات الأرض</h6>
    </div>

    <div class="col-md-6">
        <label class="form-label">اسم / وصف الأرض</label>
        <input name="name" value="{{ old('name', $parcel->name) }}"
               class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach (\App\Models\LandParcel::STATUSES as $k => $v)
                <option value="{{ $k }}" @selected(old('status', $parcel->status) === $k)>{{ $v }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">المساحة (م²)</label>
        <input type="number" step="0.01" min="0" name="area_size"
               value="{{ old('area_size', $parcel->area_size) }}"
               class="form-control @error('area_size') is-invalid @enderror">
        @error('area_size') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-4">
        <label class="form-label">الموقع / الحي</label>
        <input name="location" value="{{ old('location', $parcel->location) }}"
               class="form-control @error('location') is-invalid @enderror">
        @error('location') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">المدينة</label>
        <input name="city" value="{{ old('city', $parcel->city) }}"
               class="form-control @error('city') is-invalid @enderror">
        @error('city') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label class="form-label">رقم الصك</label>
        <input name="deed_number" value="{{ old('deed_number', $parcel->deed_number) }}"
               class="form-control @error('deed_number') is-invalid @enderror">
        @error('deed_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 mt-2">
        <hr class="my-2">
        <h6 class="text-body-secondary mb-0">بيانات الشراء</h6>
    </div>

    <div class="col-md-3">
        <label class="form-label">سعر الشراء</label>
        <input type="number" step="0.01" min="0" name="purchase_price"
               value="{{ old('purchase_price', $parcel->purchase_price ?? 0) }}"
               class="form-control @error('purchase_price') is-invalid @enderror" required>
        @error('purchase_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">تاريخ الشراء</label>
        <input type="date" name="purchase_date"
               value="{{ old('purchase_date', optional($parcel->purchase_date)->format('Y-m-d')) }}"
               class="form-control @error('purchase_date') is-invalid @enderror">
        @error('purchase_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">اشْتُريت من (البائع)</label>
        <input name="purchased_from" value="{{ old('purchased_from', $parcel->purchased_from) }}"
               class="form-control @error('purchased_from') is-invalid @enderror">
        @error('purchased_from') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">هاتف البائع</label>
        <input name="purchase_phone" value="{{ old('purchase_phone', $parcel->purchase_phone) }}"
               class="form-control @error('purchase_phone') is-invalid @enderror">
        @error('purchase_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12 mt-2">
        <hr class="my-2">
        <h6 class="text-body-secondary mb-0">بيانات البيع</h6>
        <div class="form-text mb-2">املأها عند عرض الأرض للبيع أو بعد إتمام البيع.</div>
    </div>

    <div class="col-md-3">
        <label class="form-label">سعر البيع</label>
        <input type="number" step="0.01" min="0" name="sale_price"
               value="{{ old('sale_price', $parcel->sale_price) }}"
               class="form-control @error('sale_price') is-invalid @enderror">
        @error('sale_price') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">تاريخ البيع</label>
        <input type="date" name="sale_date"
               value="{{ old('sale_date', optional($parcel->sale_date)->format('Y-m-d')) }}"
               class="form-control @error('sale_date') is-invalid @enderror">
        @error('sale_date') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">بيعت إلى (المشتري)</label>
        <input name="sold_to" value="{{ old('sold_to', $parcel->sold_to) }}"
               class="form-control @error('sold_to') is-invalid @enderror">
        @error('sold_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">هاتف المشتري</label>
        <input name="sale_phone" value="{{ old('sale_phone', $parcel->sale_phone) }}"
               class="form-control @error('sale_phone') is-invalid @enderror">
        @error('sale_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

    <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $parcel->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
