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
    @if ($errors->any())
        <div class="alert alert-danger">
            <div class="fw-semibold mb-1">تعذّر حفظ الحركة:</div>
            <ul class="mb-0 small">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
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
                <div class="col-md-4">
                    <div class="small text-body-secondary">سعر متر الشراء</div>
                    <div class="fw-semibold font-monospace">
                        {{ $parcel->purchase_price_per_m2 !== null ? number_format((float) $parcel->purchase_price_per_m2, 2).' ج.م' : '—' }}
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="small text-body-secondary">سعر متر البيع</div>
                    <div class="fw-semibold font-monospace">
                        {{ $parcel->sale_price_per_m2 !== null ? number_format((float) $parcel->sale_price_per_m2, 2).' ج.م' : '—' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-partials.module-kpis :items="array_values(array_filter([
        ['label' => 'مدفوع شراء', 'value' => number_format((float) $purchasePaid, 2) . ' ج.م'],
        ['label' => 'متبقي شراء', 'value' => number_format((float) $purchaseRemaining, 2) . ' ج.م'],
        ['label' => 'محصّل بيع', 'value' => number_format((float) $salePaid, 2) . ' ج.م'],
        ['label' => 'متبقي بيع', 'value' => number_format((float) $saleRemaining, 2) . ' ج.م'],
        !empty($partsReady) && $parcel->area_size !== null
            ? ['label' => 'مساحة متبقية', 'value' => number_format((float) ($remainingArea ?? 0), 2) . ' م²']
            : null,
        !empty($partsReady)
            ? ['label' => 'أجزاء البيع', 'value' => (int) ($parts?->count() ?? 0)]
            : null,
    ]))" />

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

    @if (!empty($partsReady))
        @php
            $totalAreaJs = (float) ($parcel->area_size ?? 0);
            $remainingAreaJs = (float) ($remainingArea ?? 0);
        @endphp
        <div class="card app-surface mb-3">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <h6 class="mb-0">بيع أجزاء من الأرض</h6>
                    <div class="small text-body-secondary">بيع أجزاء وتحصيلها مع استمرار سداد أقساط الشراء.</div>
                </div>
                @if ($parcel->area_size !== null)
                    <div class="d-flex flex-wrap gap-2">
                        <span class="badge text-bg-light border">
                            إجمالي: <span class="font-monospace">{{ number_format((float) $parcel->area_size, 2) }}</span> م²
                        </span>
                        <span class="badge text-bg-success-subtle text-success border border-success-subtle">
                            متبقي: <span class="font-monospace">{{ number_format((float) ($remainingArea ?? 0), 2) }}</span> م²
                        </span>
                    </div>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped align-middle mb-0">
                        <thead>
                        <tr>
                            <th>الجزء</th>
                            <th>المساحة / النسبة</th>
                            <th>المشتري</th>
                            <th>تاريخ البيع</th>
                            <th class="text-end">سعر البيع</th>
                            <th class="text-end">محصّل</th>
                            <th class="text-end">متبقي</th>
                            <th>الحالة</th>
                            <th class="text-end">إجراء</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse ($parts as $part)
                            @php
                                $pCollected = $part->approvedPaidTotal();
                                $pRemaining = $part->remainingTotal();
                                $pBadge = match ($part->status) {
                                    'available' => 'text-bg-info',
                                    'reserved' => 'text-bg-primary',
                                    'sold' => 'text-bg-success',
                                    default => 'text-bg-secondary',
                                };
                            @endphp
                            <tr>
                                <td class="fw-semibold">
                                    {{ $part->name }}
                                    <div class="small text-body-secondary">
                                        {{ ($part->sale_payment_type ?? 'cash') === 'installment' ? 'أقساط' : 'كاش' }}
                                    </div>
                                </td>
                                <td class="font-monospace small">
                                    @if ($part->area_size !== null)
                                        {{ number_format((float) $part->area_size, 2) }} م²
                                        @php $partPct = $parcel->areaPercentageOfTotal($part->area_size); @endphp
                                        @if ($partPct !== null)
                                            <div class="text-primary fw-semibold">{{ number_format($partPct, 2) }}%</div>
                                        @endif
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    {{ $part->sold_to ?: '—' }}
                                    @if ($part->sale_phone)
                                        <div class="small font-monospace text-body-secondary">{{ $part->sale_phone }}</div>
                                    @endif
                                </td>
                                <td class="font-monospace small">{{ optional($part->sale_date)->format('Y-m-d') ?: '—' }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $part->sale_price, 2) }}</td>
                                <td class="text-end font-monospace text-success-emphasis">{{ number_format($pCollected, 2) }}</td>
                                <td class="text-end font-monospace {{ $pRemaining > 0.01 ? 'text-danger-emphasis' : '' }}">{{ number_format($pRemaining, 2) }}</td>
                                <td><span class="badge {{ $pBadge }}">{{ $part->statusLabel() }}</span></td>
                                <td class="text-end">
                                    @can('land-trading.manage')
                                        @if ($pRemaining > 0.01 && !empty($paymentsReady))
                                            @if (($part->sale_payment_type ?? 'cash') === 'cash')
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="collapse" data-bs-target="#collect-part-{{ $part->id }}">تسجيل الكاش</button>
                                            @else
                                                <button type="button" class="btn btn-success btn-sm" data-bs-toggle="collapse" data-bs-target="#collect-part-{{ $part->id }}">تحصيل قسط</button>
                                            @endif
                                        @elseif ($pRemaining <= 0.01)
                                            <span class="badge text-bg-success">محصّل</span>
                                        @endif
                                        <form method="post" action="{{ route('land-trading.parts.destroy', [$parcel, $part]) }}" class="d-inline" onsubmit="return confirm('حذف الجزء؟');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">حذف</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                            @can('land-trading.manage')
                                @if ($pRemaining > 0.01 && !empty($paymentsReady) && in_array($part->sale_payment_type ?? 'cash', ['cash', 'installment'], true))
                                    @php $isPartCash = ($part->sale_payment_type ?? 'cash') === 'cash'; @endphp
                                    <tr class="collapse" id="collect-part-{{ $part->id }}">
                                        <td colspan="9" class="bg-body-tertiary">
                                            <form method="post" action="{{ route('land-trading.payments.store', $parcel) }}" class="row g-2 align-items-end p-2">
                                                @csrf
                                                <input type="hidden" name="side" value="sale">
                                                <input type="hidden" name="land_parcel_part_id" value="{{ $part->id }}">
                                                @if ($isPartCash)
                                                    <input type="hidden" name="kind" value="down_payment">
                                                    <input type="hidden" name="amount" value="{{ $pRemaining }}">
                                                    <input type="hidden" name="payment_method" value="cash">
                                                    <input type="hidden" name="notes" value="تحصيل كاش كامل">
                                                    <div class="col-md-3">
                                                        <div class="small text-body-secondary">تحصيل كاش كامل</div>
                                                        <div class="fw-semibold font-monospace">{{ number_format($pRemaining, 2) }} ج.م</div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">التاريخ</label>
                                                        <input type="date" name="paid_at" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                                    </div>
                                                @else
                                                    <div class="col-md-2">
                                                        <label class="form-label small">النوع</label>
                                                        <select name="kind" class="form-select form-select-sm" required>
                                                            <option value="down_payment">مقدم</option>
                                                            <option value="installment" selected>قسط</option>
                                                            <option value="other">أخرى</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">المبلغ</label>
                                                        <input type="number" step="0.01" min="0.01" max="{{ $pRemaining }}" name="amount" class="form-control form-control-sm font-monospace" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">التاريخ</label>
                                                        <input type="date" name="paid_at" class="form-control form-control-sm" value="{{ now()->toDateString() }}" required>
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">الطريقة</label>
                                                        <select name="payment_method" class="form-select form-select-sm" required>
                                                            <option value="cash">نقدي</option>
                                                            <option value="bank_transfer">تحويل</option>
                                                            <option value="check">شيك</option>
                                                        </select>
                                                    </div>
                                                @endif
                                                <div class="col-md-3">
                                                    <label class="form-label small">دخل حساب مين</label>
                                                    <select name="received_by_shareholder_id" class="form-select form-select-sm" {{ ($parcelShareholders ?? collect())->isNotEmpty() ? 'required' : '' }}>
                                                        <option value="">— اختر المساهم —</option>
                                                        @foreach ($parcelShareholders ?? [] as $row)
                                                            <option value="{{ $row->shareholder_id }}">{{ $row->shareholder->name }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                @unless ($isPartCash)
                                                    <div class="col-md-2">
                                                        <label class="form-label small">ملاحظة</label>
                                                        <input name="notes" class="form-control form-control-sm" placeholder="اختياري">
                                                    </div>
                                                @endunless
                                                <div class="col-md-2">
                                                    <button type="submit" class="btn btn-success btn-sm w-100">{{ $isPartCash ? 'تسجيل الكاش' : 'حفظ التحصيل' }}</button>
                                                </div>
                                            </form>
                                            @if ($part->installmentScheduleWithPaymentSummary() !== [])
                                                <div class="px-2 pb-2 small">
                                                    جدول الجزء:
                                                    @foreach ($part->installmentScheduleWithPaymentSummary() as $row)
                                                        <span class="badge text-bg-light me-1">
                                                            {{ $row['label'] }} {{ $row['due_date']->format('Y-m-d') }} —
                                                            متبقي {{ number_format($row['balance'], 2) }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endif
                            @endcan
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    لا توجد أجزاء بعد. أضف جزءًا بالأسفل لبدء البيع بالتقسيط/الكاش على جزء من الأرض.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @can('land-trading.manage')
                <div class="card-footer bg-body-tertiary bg-opacity-50 border-top">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <div class="fw-semibold">إضافة جزء للبيع</div>
                            <div class="small text-body-secondary">املأ البيانات بالترتيب — السعر يُحسب من مساحة الجزء × سعر متر البيع.</div>
                        </div>
                        @if (($parcel->sale_price_per_m2 ?? 0) > 0)
                            <span class="badge text-bg-primary">سعر متر البيع: {{ number_format((float) $parcel->sale_price_per_m2, 2) }} ج.م</span>
                        @else
                            <a href="{{ route('land-trading.edit', $parcel) }}" class="btn btn-outline-warning btn-sm">حدّد سعر متر البيع من تعديل الأرض</a>
                        @endif
                    </div>

                    <form method="post" action="{{ route('land-trading.parts.store', $parcel) }}" id="add-part-form">
                        @csrf
                        <input type="hidden" name="status" value="{{ old('status', 'available') }}">

                        <div class="border rounded-3 p-3 mb-3 bg-body">
                            <div class="small text-body-secondary fw-semibold mb-2">1) بيانات الجزء والمشتري</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">اسم الجزء</label>
                                    <input name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="مثال: أمامية / خلفية" required>
                                    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">المشتري</label>
                                    <input name="sold_to" class="form-control" value="{{ old('sold_to') }}" placeholder="اسم المشتري">
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">هاتف المشتري</label>
                                    <input name="sale_phone" class="form-control font-monospace" value="{{ old('sale_phone') }}">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">تاريخ البيع</label>
                                    <input type="date" name="sale_date" class="form-control" value="{{ old('sale_date', now()->toDateString()) }}">
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 mb-3 bg-body">
                            <div class="small text-body-secondary fw-semibold mb-2">2) المساحة والسعر</div>
                            <div class="row g-3 align-items-start">
                                <div class="col-md-4">
                                    <label class="form-label">المساحة (م²)</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01"
                                               @if ($parcel->area_size !== null) max="{{ $remainingAreaJs }}" @endif
                                               name="area_size" id="part_area_size"
                                               class="form-control font-monospace @error('area_size') is-invalid @enderror"
                                               value="{{ old('area_size') }}"
                                               @if ($parcel->area_size !== null) required @endif
                                               placeholder="0.00">
                                        <span class="input-group-text fw-semibold text-primary" id="part_area_percent" style="min-width: 5rem;">0%</span>
                                    </div>
                                    @error('area_size') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                                    @if ($parcel->area_size !== null)
                                        <div class="form-text mb-0">
                                            متاح للبيع: <span class="font-monospace">{{ number_format($remainingAreaJs, 2) }}</span> م²
                                            من أصل <span class="font-monospace">{{ number_format($totalAreaJs, 2) }}</span>
                                        </div>
                                        <div class="invalid-feedback d-none" id="part_area_client_error">المساحة أكبر من المتاح.</div>
                                    @endif
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">سعر البيع الإجمالي</label>
                                    <div class="input-group">
                                        <input type="number" step="0.01" min="0.01" name="sale_price" id="part_sale_price"
                                               class="form-control font-monospace" value="{{ old('sale_price') }}" required>
                                        <span class="input-group-text">ج.م</span>
                                    </div>
                                    <div class="form-text mb-0" id="part_sale_price_hint">
                                        @if (($parcel->sale_price_per_m2 ?? 0) > 0)
                                            يُحسب تلقائيًا: المساحة × {{ number_format((float) $parcel->sale_price_per_m2, 2) }}
                                        @else
                                            أدخل السعر يدويًا أو حدّد سعر المتر من تعديل الأرض
                                        @endif
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">معاينة سريعة</label>
                                    <div class="rounded-3 border px-3 py-2 h-100">
                                        <div class="small text-body-secondary">النسبة من الأرض</div>
                                        <div class="fs-5 fw-bold text-primary" id="part_area_percent_large">0%</div>
                                        <div class="small text-body-secondary mt-1">الناتج المتوقع</div>
                                        <div class="fw-semibold font-monospace" id="part_sale_total_preview">—</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="border rounded-3 p-3 mb-3 bg-body">
                            <div class="small text-body-secondary fw-semibold mb-2">3) طريقة التحصيل</div>
                            <div class="row g-3 align-items-end">
                                <div class="col-md-3">
                                    <label class="form-label">الطريقة</label>
                                    <select name="sale_payment_type" id="part_sale_payment_type" class="form-select" required>
                                        <option value="cash" @selected(old('sale_payment_type', 'cash') === 'cash')>كاش (تحصيل كامل فورًا)</option>
                                        <option value="installment" @selected(old('sale_payment_type') === 'installment')>أقساط</option>
                                    </select>
                                </div>
                                <div class="col-md-3 part-installment-fields">
                                    <label class="form-label">المقدم</label>
                                    <input type="number" step="0.01" min="0" name="sale_down_payment" class="form-control font-monospace" value="{{ old('sale_down_payment') }}">
                                </div>
                                <div class="col-md-2 part-installment-fields">
                                    <label class="form-label">مدة (شهر)</label>
                                    <input type="number" min="1" name="sale_installment_months" class="form-control" value="{{ old('sale_installment_months') }}">
                                </div>
                                <div class="col-md-2 part-installment-fields">
                                    <label class="form-label">نظام القسط</label>
                                    <select name="sale_installment_schedule" class="form-select">
                                        <option value="monthly">شهري</option>
                                        <option value="quarterly">كل 3 شهور</option>
                                        <option value="semiannual">كل 6 شهور</option>
                                    </select>
                                </div>
                                <div class="col-md-2 part-installment-fields">
                                    <label class="form-label">بداية الأقساط</label>
                                    <input type="date" name="sale_installment_start_date" class="form-control" value="{{ old('sale_installment_start_date') }}">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap gap-2 justify-content-end">
                            <button type="submit" class="btn btn-primary px-4">
                                <i class="fa-solid fa-plus ms-1"></i> إضافة الجزء
                            </button>
                        </div>
                    </form>
                </div>
            @endcan
        </div>
        @push('scripts')
        <script>
        (function () {
            function togglePartInstallment() {
                var type = document.getElementById('part_sale_payment_type')?.value;
                document.querySelectorAll('.part-installment-fields').forEach(function (el) {
                    el.style.display = type === 'installment' ? '' : 'none';
                });
            }
            document.getElementById('part_sale_payment_type')?.addEventListener('change', togglePartInstallment);
            togglePartInstallment();

            var totalArea = {{ json_encode($totalAreaJs ?? 0) }};
            var remainingArea = {{ json_encode($remainingAreaJs ?? 0) }};
            var salePerM2 = {{ json_encode((float) ($parcel->sale_price_per_m2 ?? 0)) }};
            var areaInput = document.getElementById('part_area_size');
            var percentEl = document.getElementById('part_area_percent');
            var errEl = document.getElementById('part_area_client_error');
            var partSalePrice = document.getElementById('part_sale_price');
            var form = document.getElementById('add-part-form');

            var percentLarge = document.getElementById('part_area_percent_large');
            var totalPreview = document.getElementById('part_sale_total_preview');

            function updatePartAreaPercent() {
                if (!areaInput || !percentEl) return;
                var val = parseFloat(areaInput.value);
                if (!isFinite(val) || val <= 0 || totalArea <= 0) {
                    percentEl.textContent = '0%';
                    if (percentLarge) percentLarge.textContent = '0%';
                    if (totalPreview) totalPreview.textContent = '—';
                    areaInput.classList.remove('is-invalid');
                    if (errEl) errEl.classList.add('d-none');
                    return;
                }
                var pct = (val / totalArea) * 100;
                var pctText = pct.toFixed(2) + '%';
                percentEl.textContent = pctText;
                if (percentLarge) percentLarge.textContent = pctText;
                var total = 0;
                if (salePerM2 > 0 && partSalePrice) {
                    total = val * salePerM2;
                    partSalePrice.value = total.toFixed(2);
                } else {
                    total = parseFloat(partSalePrice?.value) || 0;
                }
                if (totalPreview) {
                    totalPreview.textContent = total > 0
                        ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م'
                        : '—';
                }
                var over = remainingArea > 0 && val > remainingArea + 0.0001;
                if (over) {
                    areaInput.classList.add('is-invalid');
                    if (errEl) {
                        errEl.textContent = 'المساحة أكبر من المتاح (' + remainingArea.toLocaleString('en-US', {maximumFractionDigits: 2}) + ' م²).';
                        errEl.classList.remove('d-none');
                    }
                } else {
                    areaInput.classList.remove('is-invalid');
                    if (errEl) errEl.classList.add('d-none');
                }
            }
            partSalePrice?.addEventListener('input', function () {
                if (salePerM2 > 0) return;
                var total = parseFloat(partSalePrice.value) || 0;
                if (totalPreview) {
                    totalPreview.textContent = total > 0
                        ? total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' ج.م'
                        : '—';
                }
            });

            areaInput?.addEventListener('input', updatePartAreaPercent);
            areaInput?.addEventListener('change', updatePartAreaPercent);
            form?.addEventListener('submit', function (e) {
                var val = parseFloat(areaInput?.value);
                if (totalArea > 0 && (!isFinite(val) || val <= 0)) {
                    e.preventDefault();
                    areaInput?.classList.add('is-invalid');
                    if (errEl) {
                        errEl.textContent = 'أدخل مساحة صحيحة.';
                        errEl.classList.remove('d-none');
                    }
                    return;
                }
                if (remainingArea > 0 && isFinite(val) && val > remainingArea + 0.0001) {
                    e.preventDefault();
                    updatePartAreaPercent();
                    areaInput?.focus();
                }
            });
            updatePartAreaPercent();
        })();
        </script>
        @endpush
    @endif

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
                                    <div class="col-md-2">
                                        <label class="form-label small">النوع</label>
                                        <select name="kind" class="form-select form-select-sm" required>
                                            <option value="down_payment">مقدم</option>
                                            <option value="installment" selected>قسط</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
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
                                    <div class="col-md-3">
                                        <label class="form-label small">خرج من حساب مين</label>
                                        <select name="paid_by_shareholder_id" class="form-select form-select-sm" {{ ($parcelShareholders ?? collect())->isNotEmpty() ? 'required' : '' }}>
                                            <option value="">— اختر المساهم —</option>
                                            @foreach ($parcelShareholders ?? [] as $row)
                                                <option value="{{ $row->shareholder_id }}" @selected((string) old('paid_by_shareholder_id') === (string) $row->shareholder_id)>
                                                    {{ $row->shareholder->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-danger btn-sm w-100">سداد</button>
                                    </div>
                                    <div class="col-12">
                                        <input name="notes" class="form-control form-control-sm" placeholder="ملاحظة (اختياري)" value="{{ old('side') === 'purchase' ? old('notes') : '' }}">
                                        <div class="form-text">يُخصم من حساب المساهم ويُحسب في تمويله الفعلي ويظهر في بروفايله</div>
                                    </div>
                                </form>
                            </div>
                        @endif
                    @endcan
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card app-surface h-100">
                    <div class="card-header">
                        <h6 class="mb-0">بيع كامل الأرض (اختياري)</h6>
                        <div class="small text-body-secondary">لو بتبيع الأرض دفعة واحدة. للأجزاء استخدم القسم أعلاه.</div>
                    </div>
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
                                    <div class="col-md-2">
                                        <label class="form-label small">النوع</label>
                                        <select name="kind" class="form-select form-select-sm" required>
                                            <option value="down_payment">مقدم</option>
                                            <option value="installment" selected>قسط</option>
                                            <option value="other">أخرى</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
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
                                    <div class="col-md-3">
                                        <label class="form-label small">دخل حساب مين</label>
                                        <select name="received_by_shareholder_id" class="form-select form-select-sm" {{ ($parcelShareholders ?? collect())->isNotEmpty() ? 'required' : '' }}>
                                            <option value="">— اختر المساهم —</option>
                                            @foreach ($parcelShareholders ?? [] as $row)
                                                <option value="{{ $row->shareholder_id }}" @selected((string) old('received_by_shareholder_id') === (string) $row->shareholder_id)>
                                                    {{ $row->shareholder->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-success btn-sm w-100">تحصيل</button>
                                    </div>
                                    <div class="col-12">
                                        <input name="notes" class="form-control form-control-sm" placeholder="ملاحظة (اختياري)" value="{{ old('side') === 'sale' ? old('notes') : '' }}">
                                        <div class="form-text">يدخل صندوق الأراضي وجاري المساهم المختار ويظهر في بروفايله</div>
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
                            <th>الجزء</th>
                            <th>النوع</th>
                            <th class="text-end">المبلغ</th>
                            <th>خرج من حساب</th>
                            <th>دخل حساب</th>
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
                                <td class="small">{{ $payment->part?->name ?? '—' }}</td>
                                <td>{{ $payment->kindLabel() }}</td>
                                <td class="text-end font-monospace">{{ number_format((float) $payment->amount, 2) }}</td>
                                <td class="small">
                                    @if ($payment->side === 'purchase')
                                        {{ $payment->paidByShareholder?->name ?? '—' }}
                                    @else
                                        <span class="text-body-secondary">المشتري</span>
                                    @endif
                                </td>
                                <td class="small">
                                    @if ($payment->side === 'sale')
                                        {{ $payment->receivedByShareholder?->name ?? '—' }}
                                        <div class="text-body-secondary" style="font-size: .75rem;">
                                            {{ ($payment->distribution_status ?? '') === 'distributed' ? 'في الجاري' : (($payment->distribution_status ?? '') === 'pending' ? 'بانتظار التوزيع' : '') }}
                                        </div>
                                    @else
                                        <span class="text-body-secondary">صندوق الأراضي</span>
                                    @endif
                                </td>
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
                            <tr><td colspan="11" class="text-center text-muted py-4">لا توجد دفعات مسجّلة بعد.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-0">مساهمو الأرض — مخطط / فعلي</h6>
                <div class="small text-body-secondary">
                    المخطط = الاتفاق · الفعلي = التمويل المدفوع · رأس مال مخطط:
                    <span class="font-monospace">{{ number_format((float) ($parcel->planned_capital ?? $parcel->purchase_price), 2) }}</span>
                    · فعلي:
                    <span class="font-monospace">{{ number_format((float) ($parcel->actual_capital ?? 0), 2) }}</span>
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('land-trading.manage')
                    @if ($ownershipReady ?? false)
                        <form method="post" action="{{ route('land-trading.adopt-plan', $parcel) }}" onsubmit="return confirm('اعتماد المخطط كفعلي؟ سيتم مطابقة التمويل الفعلي بالمخطط.');">
                            @csrf
                            <button type="submit" class="btn btn-warning btn-sm">اعتماد المخطط كفعلي</button>
                        </form>
                    @endif
                @endcan
                @can('shareholders.manage')
                    <a href="{{ route('shareholders.index') }}" class="btn btn-outline-primary btn-sm">إدارة المساهمين</a>
                @endcan
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>المساهم</th>
                        <th class="text-end">تمويل مخطط</th>
                        <th class="text-end">نسبة مخطط</th>
                        <th class="text-end">تمويل فعلي</th>
                        <th class="text-end">نسبة فعلي</th>
                        <th class="text-end">فجوة</th>
                        <th class="text-end">بروفايل</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($parcelShareholders ?? [] as $row)
                        @php
                            $plannedInv = (float) ($row->planned_investment ?? $row->total_investment ?? 0);
                            $actualInv = (float) ($row->actual_investment ?? 0);
                            $gap = round($plannedInv - $actualInv, 2);
                        @endphp
                        <tr>
                            <td class="fw-semibold">{{ $row->shareholder->name }}</td>
                            <td class="text-end font-monospace">{{ number_format($plannedInv, 2) }}</td>
                            <td class="text-end">{{ number_format((float) ($row->planned_percentage ?? $row->share_percentage ?? 0), 2) }}%</td>
                            <td class="text-end font-monospace">{{ number_format($actualInv, 2) }}</td>
                            <td class="text-end">{{ number_format((float) ($row->actual_percentage ?? 0), 2) }}%</td>
                            <td class="text-end font-monospace {{ $gap > 0.01 ? 'text-danger' : ($gap < -0.01 ? 'text-success' : '') }}">
                                {{ number_format($gap, 2) }}
                            </td>
                            <td class="text-end">
                                <a href="{{ route('shareholders.show', $row->shareholder) }}" class="btn btn-outline-info btn-sm">فتح</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">
                                لا يوجد مساهمون على هذه الأرض بعد.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @if (($pendingSalePayments ?? collect())->isNotEmpty())
        <div class="card app-surface mb-3 border-warning">
            <div class="card-header">
                <h6 class="mb-0">تحصيلات بانتظار التوزيع</h6>
                <div class="small text-body-secondary">اختر أساس التوزيع: مخطط أو فعلي (أو يدوي).</div>
            </div>
            <div class="card-body">
                @foreach ($pendingSalePayments as $pending)
                    <form method="post" action="{{ route('land-trading.payments.distribute', [$parcel, $pending]) }}" class="row g-2 align-items-end border rounded p-3 mb-2">
                        @csrf
                        <div class="col-md-3">
                            <div class="small text-body-secondary">دفعة #{{ $pending->id }} — {{ $pending->paid_at?->format('Y-m-d') }}</div>
                            <div class="fw-semibold font-monospace">{{ number_format((float) $pending->amount, 2) }} ج.م</div>
                            <div class="small">{{ $pending->part?->name ?? 'بيع كامل' }}</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small">أساس التوزيع</label>
                            <select name="basis" class="form-select form-select-sm" required>
                                <option value="planned">النسبة المخططة</option>
                                <option value="actual">النسبة الفعلية</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            @can('land-trading.manage')
                                <button type="submit" class="btn btn-primary btn-sm">توزيع على المساهمين</button>
                            @endcan
                        </div>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <div class="card app-surface mb-3">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h6 class="mb-0">توزيع تحصيلات البيع على المساهمين</h6>
                <div class="small text-body-secondary">بعد التوزيع تُسجَّل في جاري المساهم (تسوية دائن) دون تكرار حركة الصندوق.</div>
            </div>
            <span class="badge text-bg-light">
                إجمالي موزّع: {{ number_format((float) ($saleDistributions ?? collect())->sum('amount'), 2) }} ج.م
            </span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped align-middle mb-0">
                    <thead>
                    <tr>
                        <th>التاريخ</th>
                        <th>المساهم</th>
                        <th class="text-end">المبلغ</th>
                        <th>الأساس</th>
                        <th class="text-end">الجاري</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($saleDistributions ?? [] as $dist)
                        <tr>
                            <td class="font-monospace small">
                                {{ $dist->payment?->paid_at?->format('Y-m-d') ?? $dist->entry_date?->format('Y-m-d') ?? $dist->created_at?->format('Y-m-d') }}
                            </td>
                            <td class="fw-semibold">{{ $dist->shareholder?->name ?? '—' }}</td>
                            <td class="text-end font-monospace text-success-emphasis">+{{ number_format((float) $dist->amount, 2) }}</td>
                            <td class="small">
                                @if (method_exists($dist, 'basisLabel'))
                                    {{ $dist->basisLabel() }}
                                    @if ($dist->percentage_used !== null)
                                        ({{ number_format((float) $dist->percentage_used, 2) }}%)
                                    @endif
                                @else
                                    {{ $dist->notes ?: '—' }}
                                @endif
                            </td>
                            <td class="text-end">
                                @if ($dist->shareholder)
                                    <a href="{{ route('shareholders.show', $dist->shareholder) }}" class="btn btn-outline-info btn-sm">فتح الجاري</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-muted py-4">
                                لا توجد توزيعات بعد. سجّل تحصيل بيع ثم وزّعه من القسم أعلاه.
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
