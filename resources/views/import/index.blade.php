@extends('layouts.admin')

@section('content')
    @php $m = fn ($v) => \App\Support\Money::display((float) $v); @endphp

    @if (! $result)
        <div class="card app-surface mb-4">
            <div class="card-body p-4">
                <h5 class="fw-semibold mb-3">نقل بياناتك القديمة في 3 خطوات</h5>
                <ol class="mb-4">
                    <li class="mb-2">سجّل العقارات (العمارات) في البرنامج الأول، بنفس الأسماء اللي في ملفك.</li>
                    <li class="mb-2">نزّل النموذج واملا كل بيعة في صف: العميل، العقار، الدور، رقم الشقة، السعر، المقدم، نظام التقسيط، واللي اتحصّل لحد النهارده.
                        <a href="{{ route('import.template', $project) }}" class="btn btn-outline-success btn-sm ms-2"><i class="fa-solid fa-file-excel ms-1"></i> تنزيل النموذج</a></li>
                    <li>ارفع الملف. هتشوف معاينة لكل صف وأخطاؤه قبل ما أي حاجة تتسجل.</li>
                </ol>
                <form method="post" action="{{ route('import.preview', $project) }}" enctype="multipart/form-data" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">ملف Excel أو CSV</label>
                        <input type="file" name="file" accept=".xlsx,.xls,.csv" class="form-control" required>
                        @error('file')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100"><i class="fa-solid fa-magnifying-glass ms-1"></i> معاينة</button>
                    </div>
                </form>
            </div>
        </div>
    @else
        <div class="row g-3 mb-3">
            <div class="col-md-4"><div class="border rounded-3 p-3"><div class="small text-body-secondary">صفوف سليمة</div><div class="fs-4 fw-bold text-success-emphasis">{{ $result['ok'] }}</div></div></div>
            <div class="col-md-4"><div class="border rounded-3 p-3"><div class="small text-body-secondary">صفوف فيها أخطاء</div><div class="fs-4 fw-bold {{ $result['failed'] ? 'text-danger' : '' }}">{{ $result['failed'] }}</div></div></div>
            <div class="col-md-4"><div class="border rounded-3 p-3"><div class="small text-body-secondary">{{ ($committed ?? false) ? 'اتسجّل فعلًا' : 'هيتسجّل' }}</div><div class="fs-4 fw-bold">{{ ($committed ?? false) ? $result['imported'] : $result['ok'] }} بيعة</div></div></div>
        </div>

        @if ($committed ?? false)
            <div class="alert alert-success">
                اتسجّل {{ $result['imported'] }} بيعة بعقودها وجدول أقساطها.
                <a href="{{ route('sales.index', $project) }}" class="alert-link">افتح المبيعات</a> ·
                <a href="{{ route('remaining.due', $project) }}" class="alert-link">الأقساط المتأخرة</a>
                @if ($result['failed'])<div class="mt-1">الصفوف اللي فيها أخطاء ما اتسجلتش — صلّحها في الملف وارفعها تاني.</div>@endif
            </div>
        @elseif ($result['ok'] > 0)
            <form method="post" action="{{ route('import.commit', $project) }}" class="card app-surface mb-3"
                  onsubmit="return confirm('تأكيد تسجيل {{ $result['ok'] }} بيعة؟')">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="keep_cashbox" value="1" id="keep-cashbox" checked>
                        <label class="form-check-label" for="keep-cashbox">
                            ما تغيّرش رصيد الصندوق <span class="small text-body-secondary">(المقدمات والتحصيلات القديمة اتصرفت قبل كده — بيتسجل قيد تسوية بيها ومش بيأثر على الربح)</span>
                        </label>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('import.index', $project) }}" class="btn btn-outline-secondary">إلغاء</a>
                        <button class="btn btn-success"><i class="fa-solid fa-check ms-1"></i> سجّل الـ {{ $result['ok'] }} بيعة السليمة</button>
                    </div>
                </div>
            </form>
        @endif

        <div class="card app-surface mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead><tr><th>سطر</th><th>العميل</th><th>الوحدة</th><th class="text-end">السعر</th><th>النظام</th><th class="text-end">محصّل</th><th>النتيجة</th></tr></thead>
                        <tbody>
                        @foreach ($result['rows'] as $row)
                            <tr class="{{ $row['errors'] ? 'table-danger' : '' }}">
                                <td>{{ $row['line'] }}</td>
                                <td>{{ $row['client'] }}</td>
                                <td>{{ $row['unit'] }}</td>
                                <td class="text-end font-monospace">{{ $m($row['price']) }}</td>
                                <td>{{ $row['type'] === 'installment' ? 'تقسيط' : ($row['type'] === 'cash' ? 'كاش' : '—') }}</td>
                                <td class="text-end font-monospace">{{ $row['collected'] ? $m($row['collected']) : '' }}</td>
                                <td class="small">
                                    @if ($row['errors'])
                                        @foreach ($row['errors'] as $err)<div>{{ $err }}</div>@endforeach
                                    @elseif ($row['sale_id'])
                                        <a href="{{ route('sales.show', [$project, $row['sale_id']]) }}">اتسجلت SL-{{ $row['sale_id'] }}</a>
                                    @else
                                        <span class="text-success-emphasis">سليم</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <a href="{{ route('import.index', $project) }}" class="btn btn-outline-secondary">ارفع ملف تاني</a>
    @endif
@endsection
