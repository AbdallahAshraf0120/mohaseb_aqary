@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="العقارات" step="3" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد العقارات', 'value' => (int) ($propertyKpis['count'] ?? 0)],
        ['label' => 'متوسط الأدوار', 'value' => number_format((float) ($propertyKpis['avg_floors'] ?? 0), 1)],
        ['label' => 'إجمالي الوحدات', 'value' => number_format((float) ($propertyKpis['sum_units'] ?? 0), 0)],
        ['label' => 'أنواع عقارات', 'value' => (int) ($propertyKpis['type_count'] ?? 0)],
    ]" />

    <x-listing.filters
        :placeholder="'اسم عقار، نوع، منطقة، أرض…'"
        :help="'التصفية حسب تاريخ إنشاء العقار.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة العقارات</h5>
            <a href="{{ route('properties.create', $project) }}" class="btn btn-primary btn-sm">إضافة عقار</a>
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
                        <th>اسم العقار</th>
                        <th>نوع العقار</th>
                        <th>الأرض</th>
                        <th>المنطقة</th>
                        <th>الأدوار المتكررة</th>
                        <th>شقق/دور</th>
                        <th>أرضي/ميزان</th>
                        <th>إجمالي الشقق</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($properties as $property)
                        <tr>
                            <td>{{ $properties->firstItem() + $loop->index }}</td>
                            <td>{{ $property->name }}</td>
                            <td>{{ $property->property_type ?? '-' }}</td>
                            <td>{{ $property->land?->name ?? ($property->land_name ?? '-') }}</td>
                            <td>{{ $property->area?->name ?? ($property->location ?? '-') }}</td>
                            <td>{{ $property->floors_count ?? '-' }}</td>
                            <td>{{ $property->apartments_per_floor ?? '-' }}</td>
                            <td class="small">
                                {{ (int) ($property->ground_floor_shops_count ?? 0) }} محل
                                ·
                                {{ $property->has_mezzanine ? ((int) ($property->mezzanine_apartments_count ?? 0) . ' شقق ميزان') : 'بدون ميزان' }}
                            </td>
                            <td>{{ $property->total_apartments ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('properties.show', [$project, $property]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                <a href="{{ route('properties.edit', [$project, $property]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('properties.destroy', [$project, $property]) }}" method="post" class="d-inline" data-swal-confirm="{{ e('هل تريد حذف هذا العقار؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">لا توجد بيانات عقارات حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $properties->links() }}</div>
        </div>
    </div>
@endsection
