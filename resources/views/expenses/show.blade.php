@extends('layouts.admin')

@section('content')
    @php
        $ref = 'EX-' . str_pad((string) $expense->id, 3, '0', STR_PAD_LEFT);
        $status = $expense->approval_status ?? 'approved';
        $statusBadge = match ($status) {
            'approved' => ['class' => 'text-bg-success', 'label' => 'معتمد'],
            'pending' => ['class' => 'text-bg-warning', 'label' => 'معلق'],
            default => ['class' => 'text-bg-secondary', 'label' => 'مرفوض'],
        };
    @endphp

    <div class="card app-surface mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div>
                <h5 class="mb-1">{{ $ref }}</h5>
                <div class="small text-body-secondary">تفاصيل مصروف مسجّل على المشروع</div>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('expenses.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع للسجل</a>
                <a href="{{ route('expenses.edit', [$project, $expense]) }}" class="btn btn-primary btn-sm">تعديل</a>
                <form method="post"
                      action="{{ route('expenses.destroy', [$project, $expense]) }}"
                      class="js-ajax-delete"
                      data-confirm="{{ e('حذف المصروف؟') }}">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                </form>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="rounded-3 border p-4 h-100 text-center text-md-start bg-body-secondary bg-opacity-25">
                        <div class="small text-body-secondary mb-2">قيمة المصروف</div>
                        <div class="fs-3 fw-bold font-monospace text-danger">{{ number_format((float) $expense->amount, 2) }}</div>
                        <div class="small text-body-secondary">جنيه مصري</div>
                        <div class="mt-3">
                            <span class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-8">
                    <dl class="row mb-0 small">
                        <dt class="col-sm-3 text-body-secondary">الفئة</dt>
                        <dd class="col-sm-9 fw-semibold">{{ $expense->category }}</dd>

                        <dt class="col-sm-3 text-body-secondary">الوصف</dt>
                        <dd class="col-sm-9">{{ $expense->description ?: '—' }}</dd>

                        <dt class="col-sm-3 text-body-secondary">تاريخ الصرف</dt>
                        <dd class="col-sm-9 font-monospace">{{ $expense->spent_at?->format('Y-m-d') ?? '—' }}</dd>

                        <dt class="col-sm-3 text-body-secondary">تاريخ الإدخال على النظام</dt>
                        <dd class="col-sm-9 font-monospace">{{ $expense->created_at?->format('Y-m-d H:i') ?? '—' }}</dd>

                        @if ($status === 'approved' && $expense->approved_at)
                            <dt class="col-sm-3 text-body-secondary">تاريخ الاعتماد</dt>
                            <dd class="col-sm-9 font-monospace">{{ $expense->approved_at instanceof \Carbon\Carbon ? $expense->approved_at->format('Y-m-d H:i') : $expense->approved_at }}</dd>
                        @endif

                        @if ($status === 'rejected' && filled($expense->rejection_reason))
                            <dt class="col-sm-3 text-body-secondary">سبب الرفض</dt>
                            <dd class="col-sm-9">{{ $expense->rejection_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-ajax-delete')) {
            return;
        }
        event.preventDefault();
        event.stopImmediatePropagation();
        if (form.dataset.busy === '1') return;
        var msg = form.getAttribute('data-confirm') || 'تأكيد الحذف؟';
        if (!window.confirm(msg)) return;
        form.dataset.busy = '1';
        var btn = form.querySelector('[type="submit"]');
        if (btn) btn.disabled = true;
        fetch(form.action, {
            method: 'POST',
            body: new FormData(form),
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            credentials: 'same-origin'
        }).then(function (res) {
            return res.json().then(function (data) {
                if (!res.ok) throw new Error((data && data.message) || 'تعذّر تنفيذ الحذف.');
                return data;
            });
        }).then(function (data) {
            if (data && data.redirect) {
                window.location.href = data.redirect;
            }
        }).catch(function (err) {
            alert(err.message || 'تعذّر تنفيذ الحذف.');
            form.dataset.busy = '0';
            if (btn) btn.disabled = false;
        });
    }, true);
})();
</script>
@endpush
