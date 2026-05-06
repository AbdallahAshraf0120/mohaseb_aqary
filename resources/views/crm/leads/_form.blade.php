@php
    /** @var \App\Models\CrmLead $lead */
    $statuses = [
        'new' => 'جديد',
        'follow_up' => 'متابعة',
        'interested' => 'مهتم',
        'won' => 'تم',
        'lost' => 'مفقود',
    ];
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">الاسم</label>
        <input name="name" value="{{ old('name', $lead->name) }}" class="form-control @error('name') is-invalid @enderror" required>
        @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">الهاتف</label>
        <input name="phone" value="{{ old('phone', $lead->phone) }}" class="form-control @error('phone') is-invalid @enderror">
        @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">البريد</label>
        <input name="email" value="{{ old('email', $lead->email) }}" class="form-control @error('email') is-invalid @enderror">
        @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">المصدر</label>
        <input name="source" value="{{ old('source', $lead->source) }}" class="form-control @error('source') is-invalid @enderror" placeholder="ads / whatsapp / referral ...">
        @error('source') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label class="form-label">الحالة</label>
        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
            @foreach ($statuses as $k => $v)
                <option value="{{ $k }}" @selected(old('status', $lead->status ?? 'new') === $k)>{{ $v }}</option>
            @endforeach
        </select>
        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-6">
        <label class="form-label">موعد المتابعة القادمة</label>
        <input type="datetime-local"
               name="next_follow_up_at"
               value="{{ old('next_follow_up_at', $lead->next_follow_up_at?->format('Y-m-d\TH:i')) }}"
               class="form-control @error('next_follow_up_at') is-invalid @enderror">
        @error('next_follow_up_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-12">
        <label class="form-label">ملاحظات</label>
        <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $lead->notes) }}</textarea>
        @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>

