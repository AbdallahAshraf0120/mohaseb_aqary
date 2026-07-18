@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'عدد المساهمين', 'value' => (int) ($shareholderKpis['count'] ?? 0)],
        ['label' => 'روابط المشاريع', 'value' => (int) ($shareholderKpis['memberships_count'] ?? 0)],
        ['label' => 'مجموع رأس المال (دفتر)', 'value' => number_format((float) ($shareholderKpis['total_investment'] ?? 0), 2) . ' ج.م'],
        ['label' => 'مجموع جاري المساهمين', 'value' => number_format((float) ($shareholderKpis['ledger_balance_total'] ?? 0), 2) . ' ج.م'],
    ]" />

    <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
        <div class="card-header bg-body-secondary border-0 py-3 px-4">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
                <div class="fw-semibold"><i class="fa-solid fa-magnifying-glass ms-1 text-body-secondary"></i> بحث وتصفية</div>
                @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to') || request()->filled('project_id'))
                    <a href="{{ route('shareholders.index') }}" class="btn btn-sm btn-outline-secondary">مسح الفلاتر</a>
                @endif
            </div>
        </div>
        <div class="card-body p-4">
            <form method="get" action="{{ route('shareholders.index') }}" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label small text-body-secondary mb-1">مشروع مشارك فيه</label>
                    <select name="project_id" class="form-select">
                        <option value="">كل المشاريع</option>
                        @foreach ($projects as $p)
                            <option value="{{ $p->id }}" @selected((string) ($selectedProjectId ?? '') === (string) $p->id)>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-body-secondary mb-1">نص البحث</label>
                    <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="اسم المساهم…" maxlength="200">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-body-secondary mb-1">من تاريخ</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label small text-body-secondary mb-1">إلى تاريخ</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">تطبيق</button>
                </div>
            </form>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة المساهمين</h5>
            @can('shareholders.manage')
                <a href="{{ route('shareholders.create') }}" class="btn btn-primary btn-sm">إضافة مساهم</a>
            @endcan
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>اسم المساهم</th>
                        <th>المشاريع</th>
                        <th class="text-end">رأس المال (دفتر)</th>
                        <th class="text-end">جاري موحّد</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($shareholders as $shareholder)
                        <tr>
                            <td>{{ $shareholders->firstItem() + $loop->index }}</td>
                            <td class="fw-semibold">{{ $shareholder->name }}</td>
                            <td>
                                @forelse ($shareholder->projectMemberships as $m)
                                    <span class="badge text-bg-light border me-1 mb-1">
                                        {{ $m->project?->name ?? '—' }}
                                        <span class="text-muted">({{ number_format((float) $m->share_percentage, 1) }}%)</span>
                                    </span>
                                @empty
                                    <span class="text-muted">—</span>
                                @endforelse
                            </td>
                            <td class="text-end font-monospace">{{ number_format((float) ($shareholder->capital_deposits_total ?? 0), 2) }}</td>
                            <td class="text-end font-monospace fw-semibold {{ ($shareholder->ledger_balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) ($shareholder->ledger_balance ?? 0), 2) }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('shareholders.show', $shareholder) }}" class="btn btn-outline-info btn-sm">بروفايل</a>
                                @can('shareholders.manage')
                                    <a href="{{ route('shareholders.edit', $shareholder) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                    <form action="{{ route('shareholders.destroy', $shareholder) }}" method="post" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف هذا المساهم؟') }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">لا توجد بيانات مساهمين حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $shareholders->links() }}</div>
        </div>
    </div>
@endsection
