@extends('layouts.admin')

@section('content')
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
                        <th>الاسم</th>
                        <th>الموقع</th>
                        <th>السعر</th>
                        <th>الحالة</th>
                        <th>المالك</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($properties as $property)
                        <tr>
                            <td>{{ $property->id }}</td>
                            <td>{{ $property->name }}</td>
                            <td>{{ $property->location }}</td>
                            <td>{{ number_format((float) $property->price, 2) }}</td>
                            <td>{{ $property->status }}</td>
                            <td>{{ $property->owner?->name ?? '-' }}</td>
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
                            <td colspan="7" class="text-center text-muted">لا توجد بيانات عقارات حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div>{{ $properties->links() }}</div>
        </div>
    </div>
@endsection
