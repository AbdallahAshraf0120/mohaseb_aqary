@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="التسويات" step="11" />

    <x-listing.filters
        :placeholder="'بحث في تفاصيل التحصيل أو المصروف…'"
        :help="'يُطبَّق على إجمالي التحصيلات (تاريخ الدفع) والمصروفات (تاريخ التسجيل) أعلاه.'"
    />

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">إجمالي التحصيلات</div>
                <div class="fs-4 fw-bold font-monospace text-success-emphasis">{{ number_format((float) $revenues, 2) }}</div>
                <div class="small text-muted">ج.م</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-4 border p-4 h-100 bg-body-secondary bg-opacity-25">
                <div class="small text-body-secondary mb-1">إجمالي المصروفات</div>
                <div class="fs-4 fw-bold font-monospace text-danger-emphasis">{{ number_format((float) $expenses, 2) }}</div>
                <div class="small text-muted">ج.م</div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="rounded-4 border p-4 h-100 bg-primary-subtle">
                <div class="small text-body-secondary mb-1">صافي التسوية</div>
                <div class="fs-4 fw-bold font-monospace">{{ number_format((float) $net, 2) }}</div>
                <div class="small text-muted">تحصيل − مصروف</div>
            </div>
        </div>
    </div>

    <div class="card app-surface mb-4">
        <div class="card-header">
            <h5 class="mb-0 fw-semibold">ملخص التسويات</h5>
        </div>
        <div class="card-body text-body-secondary">
            <p class="mb-2">شاشة تشغيلية لمراجعة توازن التحصيلات والمصروفات ضمن المشروع والفترة والبحث المحددين أعلاه.</p>
            <p class="mb-0 small">للتفاصيل الكاملة استخدم <a href="{{ route('revenues.index') }}">التحصيلات</a> و <a href="{{ route('expenses.index') }}">المصروفات</a> و <a href="{{ route('reports.index') }}">التقارير</a>.</p>
        </div>
    </div>
@endsection
