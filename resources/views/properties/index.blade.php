@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="العقارات" step="3" />
    <x-partials.module-kpis :items="[
        ['label' => 'عدد العقارات', 'value' => $properties->total()],
        ['label' => 'متوسط الأدوار', 'value' => number_format((float) $properties->avg('floors_count'), 1)],
        ['label' => 'إجمالي الوحدات', 'value' => number_format((float) $properties->sum('total_apartments'))],
        ['label' => 'أنواع عقارات', 'value' => $properties->pluck('property_type')->filter()->unique()->count()],
    ]" />

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">قائمة العقارات</h5>
            <a href="{{ route('properties.create') }}" class="btn btn-primary btn-sm">إضافة عقار</a>
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
                            <td>{{ $property->id }}</td>
                            <td>{{ $property->name }}</td>
                            <td>{{ $property->property_type ?? '-' }}</td>
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
                                <a href="{{ route('properties.show', $property) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                <a href="{{ route('properties.edit', $property) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('properties.destroy', $property) }}" method="post" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            onclick="return confirm('هل تريد حذف هذا العقار؟')">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center text-muted">لا توجد بيانات عقارات حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $properties->links() }}</div>
        </div>
    </div>
@endsection
