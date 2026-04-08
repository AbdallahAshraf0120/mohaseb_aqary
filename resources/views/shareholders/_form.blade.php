@csrf

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">اسم المساهم</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $shareholder->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">نسبة المساهمة (%)</label>
        <input type="number" step="0.01" min="0" max="100" name="share_percentage" class="form-control"
               value="{{ old('share_percentage', $shareholder->share_percentage ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">رأس المال</label>
        <input type="number" step="0.01" min="0" name="total_investment" class="form-control"
               value="{{ old('total_investment', $shareholder->total_investment ?? '') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">الأرباح المحققة</label>
        <input type="number" step="0.01" min="0" name="profit_amount" class="form-control"
               value="{{ old('profit_amount', $shareholder->profit_amount ?? 0) }}">
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
