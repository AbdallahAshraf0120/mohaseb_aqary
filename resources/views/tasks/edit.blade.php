@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل مهمة</h5>
            <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('tasks.update', $task) }}">
                @csrf
                @method('PUT')

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">عنوان المهمة</label>
                        <input name="title"
                               value="{{ old('title', $task->title) }}"
                               class="form-control @error('title') is-invalid @enderror"
                               required>
                        @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">إسناد إلى</label>
                        <select name="assigned_to" class="form-select @error('assigned_to') is-invalid @enderror" required>
                            <option value="">اختر مستخدم</option>
                            @foreach ($brokers as $u)
                                <option value="{{ $u->id }}"
                                        @selected((string) old('assigned_to', $task->assigned_to) === (string) $u->id)>
                                    {{ $u->name }} @if($u->role) ({{ $u->role }}) @endif
                                </option>
                            @endforeach
                        </select>
                        @error('assigned_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">عميل للمتابعة (اختياري)</label>
                        <input type="text"
                               id="leadSearch"
                               class="form-control mb-2"
                               placeholder="ابحث بالاسم أو الهاتف…">
                        <select name="crm_lead_id" class="form-select @error('crm_lead_id') is-invalid @enderror">
                            <option value="">بدون عميل</option>
                            @foreach (($leads ?? collect()) as $lead)
                                <option value="{{ $lead->id }}"
                                        data-search="{{ mb_strtolower(trim(($lead->name ?? '') . ' ' . ($lead->phone ?? ''))) }}"
                                        @selected((string) old('crm_lead_id', $task->crm_lead_id) === (string) $lead->id)>
                                    {{ $lead->name }} @if($lead->phone) — {{ $lead->phone }} @endif
                                </option>
                            @endforeach
                        </select>
                        @error('crm_lead_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">الحالة</label>
                        <select name="status" class="form-select @error('status') is-invalid @enderror" required>
                            @foreach (['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'done' => 'تمت', 'cancelled' => 'ملغاة'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('status', $task->status) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">الأولوية</label>
                        <select name="priority" class="form-select @error('priority') is-invalid @enderror" required>
                            @foreach (['low' => 'low', 'normal' => 'normal', 'high' => 'high', 'urgent' => 'urgent'] as $k => $v)
                                <option value="{{ $k }}" @selected(old('priority', $task->priority) === $k)>{{ $v }}</option>
                            @endforeach
                        </select>
                        @error('priority') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">تاريخ الاستحقاق</label>
                        <input type="datetime-local"
                               name="due_at"
                               value="{{ old('due_at', $task->due_at?->format('Y-m-d\TH:i')) }}"
                               class="form-control @error('due_at') is-invalid @enderror">
                        @error('due_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="col-12">
                        <label class="form-label">تفاصيل المهمة</label>
                        <textarea name="description"
                                  rows="5"
                                  class="form-control @error('description') is-invalid @enderror">{{ old('description', $task->description) }}</textarea>
                        @error('description') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary">حفظ</button>
                    <a href="{{ route('tasks.show', $task) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>

    <script>
        (function () {
            const input = document.getElementById('leadSearch');
            const select = document.querySelector('select[name="crm_lead_id"]');
            if (!input || !select) return;

            const options = Array.from(select.options).map(o => ({
                el: o,
                isPlaceholder: o.value === '',
                text: (o.getAttribute('data-search') || o.textContent || '').toLowerCase()
            }));

            function apply() {
                const q = (input.value || '').trim().toLowerCase();
                options.forEach(({ el, isPlaceholder, text }) => {
                    if (isPlaceholder) {
                        el.hidden = false;
                        return;
                    }
                    el.hidden = q !== '' && !text.includes(q);
                });
            }

            input.addEventListener('input', apply);
            apply();
        })();
    </script>
@endsection

