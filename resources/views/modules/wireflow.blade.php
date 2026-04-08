@extends('layouts.admin')

@section('content')
    @php
        $kpis = $moduleData['kpis'] ?? [];
        $filters = $moduleData['filters'] ?? [];
        $tableHeaders = $moduleData['tableHeaders'] ?? ['تفصيل 1', 'تفصيل 2', 'تفصيل 3', 'حالة/مرجع'];
        $rows = $moduleData['rows'] ?? [];
        $highlights = $moduleData['highlights'] ?? [];
        $formFields = $moduleData['formFields'] ?? [];
        $quickActions = $moduleData['quickActions'] ?? [];
        $nextStep = $moduleData['next'] ?? 'demo';
        $nextHref = $nextStep === 'demo' ? route('demo') : route('modules.show', $nextStep);
        $currentStepIndex = array_search($moduleKey, $demoStepOrder ?? [], true);
        $stepNumber = $currentStepIndex === false ? '-' : $currentStepIndex + 1;
        $totalSteps = count($demoStepOrder ?? []);
    @endphp

    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">{{ $module['label'] }}</h4>
                        <p class="text-muted mb-0">
                            {{ $demoContext['project'] ?? '-' }} | الفترة {{ $demoContext['period'] ?? '-' }} | العملة {{ $demoContext['currency'] ?? '-' }}
                        </p>
                    </div>
                    <div class="text-end">
                        <div class="badge text-bg-primary mb-2">الخطوة {{ $stepNumber }} من {{ $totalSteps }}</div>
                        <div class="small text-muted">Demo Wireflow</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @forelse ($kpis as $kpi)
            <div class="col-lg-3 col-md-6">
                <div class="small-box text-bg-light border">
                    <div class="inner">
                        <h5 class="mb-2">{{ $kpi['value'] ?? '-' }}</h5>
                        <p class="mb-0">{{ $kpi['label'] ?? '-' }}</p>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="alert alert-light border mb-0">لا توجد مؤشرات متاحة لهذا الموديول.</div>
            </div>
        @endforelse
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h5 class="mb-0">بيانات الموديول</h5>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach ($filters as $filter)
                            <span class="badge text-bg-secondary">{{ $filter }}</span>
                        @endforeach
                    </div>
                </div>
                <div class="card-body">
                    @if ($moduleKey === 'shareholders' && !empty($highlights))
                        <div class="alert alert-info border mb-3">
                            <div class="row g-2">
                                @foreach ($highlights as $item)
                                    <div class="col-md-4">
                                        <div class="small text-muted">{{ $item['label'] ?? '-' }}</div>
                                        <div class="fw-semibold">{{ $item['value'] ?? '-' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-striped align-middle">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>{{ $tableHeaders[0] ?? 'تفصيل 1' }}</th>
                                    <th>{{ $tableHeaders[1] ?? 'تفصيل 2' }}</th>
                                    <th>{{ $tableHeaders[2] ?? 'تفصيل 3' }}</th>
                                    <th>{{ $tableHeaders[3] ?? 'حالة/مرجع' }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($rows as $row)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $row[0] ?? '-' }}</td>
                                        <td>{{ $row[1] ?? '-' }}</td>
                                        <td>{{ $row[2] ?? '-' }}</td>
                                        <td>{{ $row[3] ?? '-' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">لا توجد صفوف عرض في هذا الموديول.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">اجراءات سريعة</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    @if ($moduleKey === 'shareholders' && !empty($formFields))
                        <div class="border rounded p-2 mb-2">
                            <h6 class="mb-2">إضافة مساهم جديد (Demo)</h6>
                            @foreach ($formFields as $field)
                                <input type="text" class="form-control form-control-sm mb-2" placeholder="{{ $field }}" disabled>
                            @endforeach
                            <button type="button" class="btn btn-sm btn-success w-100" disabled>حفظ المساهم</button>
                        </div>
                    @endif

                    @foreach ($quickActions as $action)
                        <button type="button" class="btn btn-outline-secondary text-start">{{ $action }}</button>
                    @endforeach

                    <hr>

                    <a href="{{ $nextHref }}" class="btn btn-primary">
                        {{ $nextStep === 'demo' ? 'انهاء الديمو والعودة للرئيسية' : 'الخطوة التالية في الديمو' }}
                    </a>
                </div>
            </div>
        </div>
    </div>
@endsection
