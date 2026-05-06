@extends('layouts.admin')

@section('content')
    @php
        $statuses = [
            'new' => 'جديد',
            'follow_up' => 'متابعة',
            'interested' => 'مهتم',
            'won' => 'تم',
            'lost' => 'مفقود',
        ];
        $activityTypes = [
            'call' => 'مكالمة',
            'whatsapp' => 'واتساب',
            'meeting' => 'مقابلة',
            'note' => 'ملاحظة',
        ];
    @endphp

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $lead->name }}</h5>
                <div class="small text-body-secondary">
                    <span class="badge text-bg-light border">{{ $statuses[$lead->status] ?? $lead->status }}</span>
                    @if ($lead->source)
                        <span class="ms-2">المصدر: {{ $lead->source }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('crm-leads.edit', $lead) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                <a href="{{ route('crm-leads.index') }}" class="btn btn-outline-secondary btn-sm">القائمة</a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-body-secondary">الهاتف</div>
                    <div class="fw-semibold font-monospace">{{ $lead->phone ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">البريد</div>
                    <div class="fw-semibold">{{ $lead->email ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">المتابعة القادمة</div>
                    <div class="fw-semibold font-monospace">
                        {{ $lead->next_follow_up_at?->format('Y-m-d H:i') ?? '—' }}
                    </div>
                </div>
                <div class="col-12">
                    <div class="small text-body-secondary">ملاحظات</div>
                    <div class="border rounded p-3 bg-body-tertiary">{{ $lead->notes ?: '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card app-surface">
                <div class="card-header">
                    <h6 class="mb-0">تسجيل متابعة</h6>
                </div>
                <div class="card-body">
                <form method="post" action="{{ route('crm-leads.activities.store', $lead) }}">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label">النوع</label>
                            <select name="type" class="form-select @error('type') is-invalid @enderror" required>
                                @foreach ($activityTypes as $k => $v)
                                    <option value="{{ $k }}" @selected(old('type', 'call') === $k)>{{ $v }}</option>
                                @endforeach
                            </select>
                            @error('type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">التاريخ/الوقت</label>
                            <input type="datetime-local"
                                   name="happened_at"
                                   value="{{ old('happened_at', now()->format('Y-m-d\TH:i')) }}"
                                   class="form-control @error('happened_at') is-invalid @enderror">
                            @error('happened_at') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-2">
                            <label class="form-label">ملحوظة</label>
                            <textarea name="note" rows="4" class="form-control @error('note') is-invalid @enderror">{{ old('note') }}</textarea>
                            @error('note') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <button class="btn btn-primary">حفظ المتابعة</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card app-surface">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">سجل المتابعات</h6>
                    <span class="badge text-bg-light border">{{ $lead->activities->count() }}</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <th style="width: 8rem">النوع</th>
                                <th>الملحوظة</th>
                                <th style="width: 10rem" class="text-end">التاريخ</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($lead->activities as $a)
                                <tr>
                                    <td>
                                        <span class="badge text-bg-secondary">{{ $activityTypes[$a->type] ?? $a->type }}</span>
                                        <div class="small text-body-secondary mt-1">{{ $a->user?->name ?? '—' }}</div>
                                    </td>
                                    <td class="small">{{ $a->note ?: '—' }}</td>
                                    <td class="text-end small font-monospace">{{ $a->happened_at?->format('Y-m-d H:i') ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">لا توجد متابعات بعد.</td>
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

