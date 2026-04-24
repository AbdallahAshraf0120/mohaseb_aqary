@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="الأراضي" step="2" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد الأراضي', 'value' => (int) ($landKpis['count'] ?? 0)],
        ['label' => 'أراضٍ مرتبطة بعقارات', 'value' => (int) ($landKpis['with_props'] ?? 0)],
    ]" />

    <x-listing.filters
        :placeholder="'اسم الأرض أو المنطقة…'"
        :help="'التصفية حسب تاريخ تسجيل الأرض.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة الأراضي</h5>
            <a href="{{ route('lands.create') }}" class="btn btn-primary btn-sm">إضافة أرض</a>
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
                        <th>اسم الأرض</th>
                        <th>المنطقة</th>
                        <th>تكلفة الأرض</th>
                        <th>إجمالي تكاليف البناء</th>
                        <th>عقارات مرتبطة</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($lands as $land)
                        @php
                            $buildTotal = (float) $land->building_license_cost
                                + (float) $land->piles_cost
                                + (float) $land->excavation_cost
                                + (float) $land->gravel_cost
                                + (float) $land->sand_cost
                                + (float) $land->cement_cost
                                + (float) $land->steel_cost
                                + (float) $land->carpentry_labor_cost
                                + (float) $land->blacksmith_labor_cost
                                + (float) $land->mason_labor_cost
                                + (float) $land->electrician_labor_cost
                                + (float) $land->tips_cost;
                        @endphp
                        <tr>
                            <td>{{ $lands->firstItem() + $loop->index }}</td>
                            <td>{{ $land->name }}</td>
                            <td>{{ $land->area?->name ?? '-' }}</td>
                            <td>{{ number_format((float) $land->land_cost, 2) }}</td>
                            <td>{{ number_format($buildTotal, 2) }}</td>
                            <td>{{ $land->properties_count }}</td>
                            <td class="text-end">
                                <a href="{{ route('lands.edit', [$project, $land]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form method="post" action="{{ route('lands.destroy', [$project, $land]) }}" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف الأرض؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد أراضٍ حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $lands->links() }}</div>
        </div>
    </div>
@endsection
