@extends('layouts.admin')

@section('content')
    @php
        $badge = match ($parcel->status) {
            'owned' => 'text-bg-info',
            'for_sale' => 'text-bg-warning',
            'reserved' => 'text-bg-primary',
            'sold' => 'text-bg-success',
            'cancelled' => 'text-bg-secondary',
            default => 'text-bg-light',
        };
        $profit = $parcel->profit();
        $purchaseTypeLabel = ($parcel->purchase_payment_type ?? 'cash') === 'installment' ? 'أقساط' : 'كاش';
        $saleTypeLabel = $parcel->sale_payment_type
            ? (($parcel->sale_payment_type === 'installment') ? 'أقساط' : 'كاش')
            : '—';
    @endphp

    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (empty($paymentsReady))
        <div class="alert alert-warning">أقساط وتحصيل الأراضي تحتاج: <code>php artisan migrate --force</code></div>
    @endif

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h5 class="mb-1">{{ $parcel->name }}</h5>
                <div class="small text-body-secondary">
                    <span class="badge {{ $badge }}">{{ $parcel->statusLabel() }}</span>
                    @if ($parcel->deed_number)
                        <span class="ms-2">الصك: <span class="font-monospace">{{ $parcel->deed_number }}</span></span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('cashbox.view')
                    <a href="{{ route('land-cashbox.index') }}" class="btn btn-outline-primary btn-sm">صندوق الأراضي</a>
                @endcan
                @can('land-trading.manage')
                    <a href="{{ route('land-trading.edit', $parcel) }}" class="btn btn-outline-warning btn-sm">تعديل</a>
                @endcan
                <a href="{{ route('land-trading.index') }}" class="btn btn-outline-secondary btn-sm">القائمة</a>
            </div>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="small text-body-secondary">الموقع</div>
                    <div class="fw-semibold">{{ $parcel->location ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">المدينة</div>
                    <div class="fw-semibold">{{ $parcel->city ?: '—' }}</div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">المساحة</div>
                    <div class="fw-semibold">
                        @if ($parcel->area_size !== null)
                            <span class="font-monospace">{{ number_format((float) $parcel->area_size, 2) }}</span> م²
                        @else
                            —
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-partials.module-kpis :items="[
        ['label' => 'مدفوع شراء', 'value' => number_format((float) $purchasePaid, 2) . ' ج.م'],
        ['label' => 'متبقي شراء', 'value' => number_format((float) $purchaseRemaining, 2) . ' ج.م'],
        ['label' => 'محصّل بيع', 'value' => number_format((float) $salePaid, 2) . ' ج.م'],
        ['label' => 'متبقي بيع', 'value' => number_format((float) $saleRemaining, 2) . ' ج.م'],
    ]" />

    <div class="row g-3 mb-3">
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header"><h6 class="mb-0">الشراء — {{ $purchaseTypeLabel }}</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-body-secondary">سعر الشراء</div>
                            <div class="fw-semibold font-monospace">{{ number_format((float) $parcel->purchase_price, 2) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">المقدم</div>
                            <div class="fw-semibold font-monospace">{{ number_format((float) ($parcel->purchase_down_payment ?? 0), 2) }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">البائع</div>
                            <div class="fw-semibold">{{ $parcel->purchased_from ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">تاريخ الشراء</div>
                            <div class="fw-semibold font-monospace">{{ optional($parcel->purchase_date)->format('Y-m-d') ?: '—' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card app-surface h-100">
                <div class="card-header"><h6 class="mb-0">البيع — {{ $saleTypeLabel }}</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="small text-body-secondary">سعر البيع</div>
                            <div class="fw-semibold font-monospace">
                                {{ $parcel->sale_price !== null ? number_format((float) $parcel->sale_price, 2) : '—' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">المقدم</div>
                            <div class="fw-semibold font-monospace">
                                {{ $parcel->sale_down_payment !== null ? number_format((float) $parcel->sale_down_payment, 2) : '—' }}
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">المشتري</div>
                            <div class="fw-semibold">{{ $parcel->sold_to ?: '—' }}</div>
                        </div>
                        <div class="col-6">
                            <div class="small text-body-secondary">تاريخ البيع</div>
                            <div class="fw-semibold font-monospace">{{ optional($parcel->sale_date)->format('Y-m-d') ?: '—' }}</div>
                        </div>
                        @if ($profit !== null)
                            <div class="col-12">
                                <div class="small text-body-secondary">الربح / الخسارة (سعر)</div>
                                <div class="fw-bold font-monospace {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">
                                    {{ number_format($profit, 2) }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if (!empty($paymentsReady))
        <div class="row g-3 mb-3">
            <div class="col-lg-6">
                <div class="card app-surface h-100">
                    <div class="card-header"><h6 class="mb-0">جدول أقساط / دفعات الشراء</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>البند</th>
                                    <th>الاستحقاق</th>
                                    <th class="text-end">المبلغ</th>
                                    <th class="text-end">مدفوع</th>
                                    <th class="text-end">متبقي</th>
                                    <th>الحالة</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($purchaseSchedule as $row)
                                    <tr>
                                        <td>{{ $row['number'] }}</td>
                                        <td>{{ $row['label'] ?? $row['kind'] }}</td>
                                        <td class="font-monospace small">{{ $row['due_date']->format('Y-m-d') }}</td>
                                        <td class="text-end font-monospace">{{ number_format($row['amount'], 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($row['paid'], 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($row['balance'], 2) }}</td>
                                        <td><span class="badge text-bg-light">{{ $row['status'] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-3">لا يوجد جدول.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @can('land-trading.manage')
                        @if ($purchaseRemaining > 0.01)
                            <div class="card-footer">
                                <form method="post" action="{{ route('land-trading.payments.store', $parcel) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <input type="hidden" name="side" value="purchase">
                                    <div class="col-md-3">
                                        <label class="form-label small">النوع</label>
                                        <select name="kind" class="form-select form-select-sm" required>
                                            <option value="down_payment">مقدم</option>
                                            <option value="installment" selected>قسط</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">المبلغ</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm font-monospace" value="{{ old('side') === 'purchase' ? old('amount') : '' }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">التاريخ</label>
                                        <input type="date" name="paid_at" class="form-control form-control-sm" value="{{ old('paid_at', now()->toDateString()) }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">الطريقة</label>
                                        <select name="payment_method" class="form-select form-select-sm" required>
                                            <option value="cash">نقدي</option>
                                            <option value="bank_transfer">تحويل</option>
                                            <option value="check">شيك</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-danger btn-sm w-100">سداد</button>
                                    </div>
                                    <div class="col-12">
                                        <input name="notes" class="form-control form-control-sm" placeholder="ملاحظة (اختياري)" value="{{ old('side') === 'purchase' ? old('notes') : '' }}">
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card app-surface h-100">
                    <div class="card-header"><h6 class="mb-0">جدول أقساط / تحصيل البيع</h6></div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>البند</th>
                                    <th>الاستحقاق</th>
                                    <th class="text-end">المبلغ</th>
                                    <th class="text-end">محصّل</th>
                                    <th class="text-end">متبقي</th>
                                    <th>الحالة</th>
                                </tr>
                                </thead>
                                <tbody>
                                @forelse ($saleSchedule as $row)
                                    <tr>
                                        <td>{{ $row['number'] }}</td>
                                        <td>{{ $row['label'] ?? $row['kind'] }}</td>
                                        <td class="font-monospace small">{{ $row['due_date']->format('Y-m-d') }}</td>
                                        <td class="text-end font-monospace">{{ number_format($row['amount'], 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($row['paid'], 2) }}</td>
                                        <td class="text-end font-monospace">{{ number_format($row['balance'], 2) }}</td>
                                        <td><span class="badge text-bg-light">{{ $row['status'] }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted py-3">حدّد سعر البيع وطريقة التحصيل من التعديل أولاً.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @can('land-trading.manage')
                        @if (($parcel->sale_price ?? 0) > 0 && $saleRemaining > 0.01)
                            <div class="card-footer">
                                <form method="post" action="{{ route('land-trading.payments.store', $parcel) }}" class="row g-2 align-items-end">
                                    @csrf
                                    <input type="hidden" name="side" value="sale">
                                    <div class="col-md-3">
                                        <label class="form-label small">النوع</label>
                                        <select name="kind" class="form-select form-select-sm" required>
                                            <option value="down_payment">مقدم</option>
                                            <option value="installment" selected>قسط</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small">المبلغ</label>
                                        <input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm font-monospace" value="{{ old('side') === 'sale' ? old('amount') : '' }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">التاريخ</label>
                                        <input type="date" name="paid_at" class="form-control form-control-sm" value="{{ old('paid_at', now()->toDateString()) }}" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small">الطريقة</label>
                                        <select name="payment_method" class="form-select form-select-sm" required>
                                            <option value="cash">نقدي</option>
                                            <option value="bank_transfer">تحويل</option>
                                            <option value="check">شيك</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success btn-sm w-100">تحصيل</button>
                                    </div>
                                    <div class="col-12">
                                        <input name="notes" class="form-control form-control-sm" placeholder="ملاحظة (اختياري)" value="{{ old('side') === 'sale' ? old('notes') : '' }}">
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>
        </div>

        <div class="card app-surface mb-3">
            <div class="card-header"><h6 class="mb-0">سجل الدفعات والتحصيلات</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الجانب</th>
                            <th>النوع</th>
                            <th class="text-end">المبلغ</th>
                            <th>الطريقة</th>
                            <th>الحالة</th>
                            <th>ملاحظة</th>
                            <th></th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($payments as $payment)
                            <tr>
                                <td class="font-monospace small">{{ $payment->paid_at?->format('Y-m-d') }}</td>
                                <td>
                                    <span class="badge {{ $payment->side === 'purchase' ? 'text-bg-danger' : 'text-bg-success' }}">
                                        {{ $payment->sideLabel() }}
                                    </span>
                                </td>
                                <td>{{ $payment->kindLabel() }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="small">{{ $payment->payment_method }}</td>
                                <td class="small">{{ $payment->approval_status }}</td>
                                <td class="small">{{ $payment->notes ?: '—' }}</td>
                                <td class="text-end">
                                    @can('land-trading.manage')
                                        <form method="post" action="{{ route('land-trading.payments.destroy', [$parcel, $payment]) }}" onsubmit="return confirm('حذف الدفعة؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-4">لا توجد دفعات مسجّلة بعد.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h6 class="mb-0">مساهمو الأرض</h6>
            @can('shareholders.manage')
                <a href="{{ route('shareholders.index') }}" class="btn btn-outline-primary btn-sm">إدارة المساهمين</a>
            @endcan
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>المساهم</th>
                        <th class="text-end">التمويل</th>
                        <th class="text-end">النسبة</th>
                        <th class="text-end">بروفايل</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($parcelShareholders ?? [] as $row)
                        <tr>
                            <td class="fw-semibold">{{ $row->shareholder->name }}</td>
                            <td class="text-end font-monospace">{{ number_format((float) $row->total_investment, 2) }}</td>
                            <td class="text-end">{{ number_format((float) $row->share_percentage, 2) }}%</td>
                            <td class="text-end">
                                <a href="{{ route('shareholders.show', $row->shareholder) }}" class="btn btn-outline-info btn-sm">فتح</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                لا يوجد مساهمون على هذه الأرض بعد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if ($parcel->notes)
        <div class="card app-surface mb-3">
            <div class="card-header"><h6 class="mb-0">ملاحظات</h6></div>
            <div class="card-body">
                <div class="border rounded p-3 bg-body-tertiary">{{ $parcel->notes }}</div>
            </div>
        </div>
    @endif

    <div class="small text-body-secondary">
        سجّلها: {{ $parcel->creator?->name ?? '—' }}
        @if ($parcel->created_at)
            — <span class="font-monospace">{{ $parcel->created_at->format('Y-m-d H:i') }}</span>
        @endif
    </div>
@endsection
