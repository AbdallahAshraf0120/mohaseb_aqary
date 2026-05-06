@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي', 'value' => (int) ($leadKpis['count'] ?? 0)],
        ['label' => 'جديد', 'value' => (int) ($leadKpis['new'] ?? 0)],
        ['label' => 'متابعة', 'value' => (int) ($leadKpis['follow_up'] ?? 0)],
        ['label' => 'مستحق متابعة الآن', 'value' => (int) ($leadKpis['due'] ?? 0)],
    ]" />

    <x-listing.filters
        :placeholder="'اسم، هاتف، بريد…'"
        :help="'تصفية حسب تاريخ إنشاء العميل المحتمل.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h5 class="mb-0">CRM - العملاء المحتملين</h5>
                @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('status'))
                    <span class="badge text-bg-primary">فلاتر نشطة</span>
                @endif
            </div>
            <a href="{{ route('crm-leads.create') }}" class="btn btn-primary btn-sm">
                <i class="fa-solid fa-circle-plus ms-1"></i> إضافة عميل
            </a>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="d-flex flex-wrap gap-2 mb-3">
                @php
                    $statuses = [
                        '' => 'الكل',
                        'new' => 'جديد',
                        'follow_up' => 'متابعة',
                        'interested' => 'مهتم',
                        'won' => 'تم',
                        'lost' => 'مفقود',
                    ];
                @endphp
                @foreach ($statuses as $key => $label)
                    @php
                        $active = (string) $status === (string) $key;
                        $qs = request()->query();
                        if ($key === '') {
                            unset($qs['status']);
                        } else {
                            $qs['status'] = $key;
                        }
                    @endphp
                    <a href="{{ route('crm-leads.index', $qs) }}"
                       class="btn btn-sm {{ $active ? 'btn-primary' : 'btn-outline-secondary' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>الاسم</th>
                        <th>الهاتف</th>
                        <th>الحالة</th>
                        <th>المتابعة القادمة</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($leads as $lead)
                        @php
                            $badge = match ($lead->status) {
                                'new' => 'text-bg-info',
                                'follow_up' => 'text-bg-primary',
                                'interested' => 'text-bg-warning',
                                'won' => 'text-bg-success',
                                'lost' => 'text-bg-secondary',
                                default => 'text-bg-light',
                            };
                            $isDue = $lead->next_follow_up_at && $lead->next_follow_up_at->lte(now());
                        @endphp
                        <tr @if($isDue) class="table-warning" @endif>
                            <td>{{ $leads->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $lead->name }}</td>
                            <td class="font-monospace small">{{ $lead->phone ?: '—' }}</td>
                            <td><span class="badge {{ $badge }}">{{ $statuses[$lead->status] ?? $lead->status }}</span></td>
                            <td class="small">
                                @if ($lead->next_follow_up_at)
                                    <span class="font-monospace">{{ $lead->next_follow_up_at->format('Y-m-d H:i') }}</span>
                                    @if ($isDue)
                                        <span class="badge text-bg-danger ms-1">متأخرة</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="text-end">
                                <a href="{{ route('crm-leads.show', $lead) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                <a href="{{ route('crm-leads.edit', $lead) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('crm-leads.destroy', $lead) }}"
                                      method="post"
                                      class="d-inline"
                                      data-swal-confirm="{{ e('هل تريد حذف هذا العميل المحتمل؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">لا توجد عملاء محتملين حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $leads->links() }}</div>
        </div>
    </div>
@endsection

