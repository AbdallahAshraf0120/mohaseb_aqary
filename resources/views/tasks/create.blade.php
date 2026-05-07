@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة مهمة</h5>
            <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('tasks.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">عنوان المهمة</label>
                        <input name="title" value="{{ old('title') }}" class="form-control @error('title') is-invalid @enderror" required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إسناد إلى</label>
                        <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" required>
                            <option value="">اختر مستخدم</option>
                            @foreach ($brokers as $u)
                                <option value="{{ $u->id }}" @selected((string) old('assigned_to') === (string) $u->id)>
                                    {{ $u->name }} @if($u->role) ({{ $u->role }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">عميل للمتابعة (اختياري)</label>
                        <select name="crm_lead_id"
                                data-tomselect
                                data-placeholder="ابحث بالاسم أو الهاتف…"
                                class="form-select @error('crm_lead_id') is-invalid @enderror">
                            <option value="">بدون عميل</option>
                            @foreach (($leads ?? collect()) as $lead)
                                <option value="{{ $lead->id }}"
                                        @selected((string) old('crm_lead_id') === (string) $lead->id)>
                                    {{ $lead->name }} @if($lead->phone) — {{ $lead->phone }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('crm_lead_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        <div class="form-text">اختيار العميل يساعد البروكر يركز المتابعة على شخص محدد.</div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach (['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'done' => 'تمت', 'cancelled' => 'ملغاة'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', 'open') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                            @foreach (['low' => 'low', 'normal' => 'normal', 'high' => 'high', 'urgent' => 'urgent'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('priority', 'normal') === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="datetime-local"
                               name="due_at"
                               value="{{ old('due_at') }}"
                               class="form-control @error('due_at') is-invalid @enderror">
                        @error('due_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">تفاصيل المهمة</label>
                        <textarea name="description" rows="5" class="form-control @error('description') is-invalid @enderror">{{ old('description') }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary">حفظ</button>
                    <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

@endsection

