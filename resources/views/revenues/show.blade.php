@extends('layouts.admin')

@section('content')
    @php
        $pmLabels = ['cash' => 'نقدي', 'bank_transfer' => 'تحويل بنكي', 'check' => 'شيك'];
        $paymentLabel = $pmLabels[$revenue->payment_method] ?? ($revenue->payment_method ?: '—');
        $ref = 'RV-' . str_pad((string) $revenue->id, 3, '0', STR_PAD_LEFT);
    @endphp

    <style>
        .revenue-show-hero {
            background: linear-gradient(
                165deg,
                rgba(var(--bs-primary-rgb), 0.18) 0%,
                rgba(var(--bs-primary-rgb), 0.06) 45%,
                var(--bs-body-bg) 100%
            );
            border-inline-start: 4px solid var(--bs-primary);
        }
        @media (min-width: 992px) {
            .revenue-show-hero {
                min-height: 100%;
            }
        }
    </style>

    <div class="card border-0 shadow rounded-4 overflow-hidden mb-4">
        <div class="card-header border-0 bg-transparent pt-4 px-4 pb-0">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge rounded-pill bg-primary-subtle text-primary border border-primary-subtle px-3 py-2">
                            <i class="fa-solid fa-money-bill-wave ms-1"></i> تحصيل
                        </span>
                        @if ($project ?? null)
                            <span class="text-body-secondary small">{{ $project->name }}</span>
                        @endif
                    </div>
                    <h2 class="h3 mb-1 fw-bold text-body">{{ $ref }}</h2>
                    <p class="text-body-secondary small mb-0">إيصال تحصيل مسجّل على المشروع</p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('revenues.index', $project) }}" class="btn btn-light border shadow-sm">
                        <i class="fa-solid fa-list ms-1"></i> رجوع للسجل
                    </a>
                    <a href="{{ route('revenues.edit', [$project, $revenue]) }}" class="btn btn-primary shadow-sm">
                        <i class="fa-solid fa-pen-to-square ms-1"></i> تعديل
                    </a>
                </div>
            </div>
        </div>

        <div class="card-body p-4">
            <div class="row g-4 align-items-stretch">
                <div class="col-lg-4">
                    <div class="revenue-show-hero rounded-4 p-4 h-100 d-flex flex-column justify-content-center text-center text-lg-start shadow-sm">
                        <div class="text-body-secondary small fw-semibold mb-2">
                            <i class="fa-solid fa-coins ms-1 opacity-75"></i> قيمة التحصيل
                        </div>
                        <div class="fs-1 fw-bold text-primary font-monospace lh-sm mb-1">
                            {{ number_format((float) $revenue->amount, 2) }}
                        </div>
                        <div class="text-body-secondary small">جنيه مصري</div>
                        <div class="mt-4 pt-3 border-top border-primary-subtle d-flex flex-wrap gap-2 justify-content-center justify-content-lg-start">
                            <span class="badge bg-white text-dark border shadow-sm px-3 py-2">{{ $revenue->category }}</span>
                            <span class="badge bg-white text-dark border shadow-sm px-3 py-2">
                                <i class="fa-solid fa-wallet ms-1 text-success"></i> {{ $paymentLabel }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="h-100 rounded-4 border bg-body-secondary bg-opacity-25 p-4 shadow-sm">
                                <h3 class="h6 text-body-secondary fw-semibold mb-4 d-flex align-items-center gap-2">
                                    <span class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width:2rem;height:2rem;font-size:.75rem;">
                                        <i class="fa-solid fa-link"></i>
                                    </span>
                                    الجهات المرتبطة
                                </h3>
                                <ul class="list-unstyled mb-0 small">
                                    <li class="d-flex py-2 border-bottom border-secondary-subtle">
                                        <span class="text-body-secondary" style="min-width: 5.5rem;"><i class="fa-regular fa-user ms-1"></i> العميل</span>
                                        <span class="fw-semibold text-truncate">{{ $revenue->client?->name ?? '—' }}</span>
                                    </li>
                                    <li class="d-flex py-2 border-bottom border-secondary-subtle">
                                        <span class="text-body-secondary" style="min-width: 5.5rem;"><i class="fa-regular fa-file-lines ms-1"></i> العقد</span>
                                        <span>
                                            @if ($revenue->contract_id)
                                                <a href="{{ route('contracts.show', [$project, $revenue->contract]) }}" class="fw-semibold text-decoration-none">
                                                    CT-{{ now()->format('Y') }}-{{ str_pad((string) $revenue->contract_id, 3, '0', STR_PAD_LEFT) }}
                                                    <i class="fa-solid fa-up-right-from-square fa-xs opacity-50 ms-1"></i>
                                                </a>
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </li>
                                    <li class="d-flex py-2">
                                        <span class="text-body-secondary" style="min-width: 5.5rem;"><i class="fa-solid fa-cart-shopping ms-1"></i> البيعة</span>
                                        <span>
                                            @if ($revenue->sale_id && $revenue->sale)
                                                <a href="{{ route('sales.show', [$project, $revenue->sale]) }}" class="fw-semibold text-decoration-none">
                                                    SL-{{ str_pad((string) $revenue->sale_id, 3, '0', STR_PAD_LEFT) }}
                                                    <i class="fa-solid fa-up-right-from-square fa-xs opacity-50 ms-1"></i>
                                                </a>
                                            @elseif ($revenue->sale_id)
                                                <span class="font-monospace">SL-{{ str_pad((string) $revenue->sale_id, 3, '0', STR_PAD_LEFT) }}</span>
                                            @else
                                                —
                                            @endif
                                        </span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="h-100 rounded-4 border bg-body-secondary bg-opacity-25 p-4 shadow-sm">
                                <h3 class="h6 text-body-secondary fw-semibold mb-4 d-flex align-items-center gap-2">
                                    <span class="rounded-circle bg-dark text-white d-inline-flex align-items-center justify-content-center" style="width:2rem;height:2rem;font-size:.75rem;">
                                        <i class="fa-solid fa-circle-info"></i>
                                    </span>
                                    تفاصيل الدفعة
                                </h3>
                                <ul class="list-unstyled mb-0 small">
                                    <li class="d-flex py-2 border-bottom border-secondary-subtle">
                                        <span class="text-body-secondary" style="min-width: 6.5rem;">تاريخ التحصيل</span>
                                        <span class="font-monospace fw-medium">{{ $revenue->paid_at?->format('Y-m-d') ?? '—' }}</span>
                                    </li>
                                    <li class="d-flex py-2 border-bottom border-secondary-subtle">
                                        <span class="text-body-secondary" style="min-width: 6.5rem;">المصدر</span>
                                        <span class="text-break">{{ $revenue->source ?: '—' }}</span>
                                    </li>
                                    @if (filled($revenue->notes))
                                        <li class="d-flex py-2">
                                            <span class="text-body-secondary" style="min-width: 6.5rem;">ملاحظات</span>
                                            <span class="text-break">{{ $revenue->notes }}</span>
                                        </li>
                                    @endif
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
