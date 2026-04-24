@extends('layouts.admin')

@section('content')
    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي التحصيل', 'value' => number_format((float) ($revenueStats['sum_amount'] ?? 0), 2) . ' ج.م'],
        ['label' => 'عدد الإيصالات', 'value' => (int) ($revenueStats['count'] ?? 0)],
        ['label' => 'متوسط التحصيل', 'value' => ($revenueStats['count'] ?? 0) > 0 ? number_format((float) ($revenueStats['avg_amount'] ?? 0), 2) . ' ج.م' : '—'],
    ]" />

    <x-listing.filters
        :placeholder="'عميل، فئة، طريقة دفع، ملاحظات…'"
        :help="'التصفية حسب تاريخ التحصيل (تاريخ الدفع). المؤشرات أعلاه تطابق نفس الفلاتر.'"
    />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">سجل التحصيل</h5>
            <a href="{{ route('revenues.create') }}" class="btn btn-primary btn-sm">تحصيل دفعة</a>
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
                        <th>رقم الإيصال</th>
                        <th>العميل</th>
                        <th>مرجع العقد</th>
                        <th>القيمة</th>
                        <th>التاريخ</th>
                        <th class="text-end">العمليات</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($revenues as $revenue)
                        <tr>
                            <td>{{ $revenues->firstItem() + $loop->index }}</td>
                            <td>RV-{{ str_pad((string) $revenue->id, 3, '0', STR_PAD_LEFT) }}</td>
                            <td>{{ $revenue->client?->name ?? '-' }}</td>
                            <td>{{ $revenue->contract_id ? 'CT-' . now()->format('Y') . '-' . str_pad((string) $revenue->contract_id, 3, '0', STR_PAD_LEFT) : '-' }}</td>
                            <td>{{ number_format((float) $revenue->amount, 2) }}</td>
                            <td>{{ $revenue->paid_at?->format('Y-m-d') ?? '-' }}</td>
                            <td class="text-end">
                                <a href="{{ route('revenues.show', [$project, $revenue]) }}" class="btn btn-outline-info btn-sm">عرض</a>
                                <a href="{{ route('revenues.edit', [$project, $revenue]) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                                <form action="{{ route('revenues.destroy', [$project, $revenue]) }}" method="post" class="d-inline" data-swal-confirm="{{ e('حذف حركة التحصيل؟') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted">لا توجد عمليات تحصيل حتى الآن.</td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $revenues->links() }}</div>
        </div>
    </div>
@endsection
