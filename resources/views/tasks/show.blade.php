@extends('layouts.admin')

@section('content')
    @php
        $statuses = [
            'open' => 'مفتوحة',
            'in_progress' => 'قيد التنفيذ',
            'done' => 'تمت',
            'cancelled' => 'ملغاة',
        ];
    @endphp

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $task->title }}</h5>
                <div class="small text-body-secondary">
                    <span class="badge text-bg-light border">{{ $statuses[$task->status] ?? $task->status }}</span>
                    <span class="ms-2">الأولوية: <span class="badge text-bg-light border">{{ $task->priority }}</span></span>
                    @if ($task->due_at)
                        <span class="ms-2">الاستحقاق: <span class="font-monospace">{{ $task->due_at->format('Y-m-d H:i') }}</span></span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('tasks.index') }}" class="btn btn-outline-secondary btn-sm">القائمة</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if ($task->description)
                <div class="border rounded p-3 bg-body-tertiary">{{ $task->description }}</div>
            @else
                <div class="text-body-secondary">لا توجد تفاصيل.</div>
            @endif

            <div class="row g-3 mt-1">
                <div class="col-md-4">
                    <div class="small text-body-secondary">المسندة إلى</div>
                    <div class="fw-semibold">{{ $task->assignee?->name ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">تم الإنشاء بواسطة</div>
                    <div class="fw-semibold">{{ $task->creator?->name ?? '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">تاريخ الإنشاء</div>
                    <div class="fw-semibold font-monospace">{{ $task->created_at?->format('Y-m-d H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card app-surface">
                <div class="card-header">
                    <h6 class="mb-0">تقرير التنفيذ / تحديث</h6>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('tasks.updates.store', $task) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">التاريخ/الوقت</label>
                            <input type="datetime-local"
                                   name="happened_at"
                                   value="{{ old('happened_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-control @error('happened_at') is-invalid @enderror">
                            @error('happened_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">التفاصيل</label>
                            <textarea name="note" rows="4" class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                            @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">مرفق (صورة/ملف)</label>
                            <input type="file" name="attachment" class="form-control @error('attachment') is-invalid @enderror">
                            @error('attachment') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">الحد الأقصى 10MB.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">تحديث الحالة</label>
                            <select name="status" class="form-select @error('status') is-invalid @enderror">
                                <option value="">بدون تغيير</option>
                                @foreach (['open' => 'مفتوحة', 'in_progress' => 'قيد التنفيذ', 'done' => 'تمت'] as $k => $v)
                                    <option value="{{ $k }}" @selected(old('status') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('status') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-primary">حفظ التحديث</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card app-surface">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">سجل التحديثات</h6>
                    <span class="badge text-bg-light border">{{ $task->updates->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 10rem">المستخدم</th>
                                <th>التفاصيل</th>
                                <th style="width: 10rem" class="text-end">التاريخ</th>
                                <th style="width: 6rem" class="text-end">مرفق</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($task->updates as $u)
                                <tr>
                                    <td class="small">
                                        <div class="fw-semibold">{{ $u->user?->name ?? '—' }}</div>
                                        <div class="text-body-secondary font-monospace">{{ $u->id }}</div>
                                    </td>
                                    <td class="small">{{ $u->note ?: '—' }}</td>
                                    <td class="text-end small font-monospace">{{ $u->happened_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td class="text-end">
                                        @if ($u->attachment_path)
                                            <a class="btn btn-outline-secondary btn-sm"
                                               href="{{ route('tasks.updates.attachment', [$task, $u]) }}"
                                               target="_blank" rel="noopener">
                                                فتح
                                            </a>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">لا توجد تحديثات بعد.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

