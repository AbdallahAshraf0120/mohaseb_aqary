@extends('layouts.admin')

@section('content')

    <x-listing.filters
        :placeholder="'بحث في تفاصيل التحصيل أو المصروف…'"
        :help="'يُطبَّق على إجمالي التحصيلات (تاريخ الدفع) والمصروفات (تاريخ التسجيل) أعلاه.'"
    />

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">إجمالي التحصيلات</div>
                <div class="fs-4 fw-bold font-monospace text-success-emphasis">{{ number_format((float) $revenues, 5) }}</div>
                <div class="small text-muted">ج.م</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">إجمالي المصروفات</div>
                <div class="fs-4 fw-bold font-monospace text-danger-emphasis">{{ number_format((float) $expenses, 5) }}</div>
                <div class="small text-muted">ج.م</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-4 border p-4 h-100 bg-primary-subtle">
                <div class="small text-body-secondary mb-1">صافي التسوية (تحصيل − مصروف)</div>
                <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $net, 5) }}</div>
                <div class="small text-muted">ملخص تشغيلي للمشروع</div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="mb-0 fw-semibold">أرصدة جاري المساهمين</h5>
            <span class="badge text-bg-light border">
                إجمالي الأرصدة: {{ number_format((float) ($shareholderLedgerTotal ?? 0), 5) }} ج.م
            </span>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary mb-3">من دفتر الجاري اليدوي. لتسجيل سحب أو تصفية مدفوعة افتح بروفايل المساهم.</p>
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>المساهم</th>
                        <th>النسبة</th>
                        <th class="text-end">رأس المال (إيداعات)</th>
                        <th class="text-end">رصيد الجاري</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($shareholders as $shareholder)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td class="fw-semibold">{{ $shareholder->name }}</td>
                            <td>{{ number_format((float) $shareholder->share_percentage, 5) }}%</td>
                            <td class="text-end font-monospace">{{ number_format((float) ($shareholder->capital_deposits_total ?? 0), 5) }}</td>
                            <td class="text-end font-monospace fw-semibold {{ ($shareholder->ledger_balance ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                {{ number_format((float) ($shareholder->ledger_balance ?? 0), 5) }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('shareholders.show', $shareholder) }}" class="btn btn-outline-info btn-sm">الجاري</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted">لا يوجد مساهمون في هذا المشروع.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0 fw-semibold">ملخص التسويات التشغيلية</h5>
        </div>
        <div class="card-body text-body-secondary">
            <p class="mb-2">البطاقات أعلاه لمراجعة توازن التحصيلات والمصروفات ضمن المشروع والفترة والبحث المحددين.</p>
            <p class="mb-0 small">للتفاصيل الكاملة استخدم <a href="{{ route('revenues.index', $project) }}">التحصيلات</a> و <a href="{{ route('expenses.index', $project) }}">المصروفات</a> و <a href="{{ route('shareholders.index', ['project_id' => $project->id]) }}">المساهمين</a>.</p>
        </div>
    </div>
@endsection
