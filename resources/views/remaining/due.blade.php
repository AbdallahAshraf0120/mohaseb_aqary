@extends('layouts.admin')

@section('content')
    @php
        $m = fn ($v) => \App\Support\Money::display((float) $v);
        $tabs = ['overdue' => 'متأخر', 'week' => 'مستحق خلال 7 أيام', 'month' => 'مستحق خلال 30 يوم'];
    @endphp

    <x-partials.module-kpis :items="[
        ['label' => 'إجمالي المتأخر', 'value' => $m($kpis['overdue_total']) . ' ج.م'],
        ['label' => 'عملاء متأخرين', 'value' => (int) $kpis['overdue_clients']],
        ['label' => 'مستحق خلال 7 أيام', 'value' => $m($kpis['week_total']) . ' ج.م'],
        ['label' => 'مستحق خلال 30 يوم', 'value' => $m($kpis['month_total']) . ' ج.م'],
    ]" />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2 d-print-none">
            <ul class="nav nav-pills gap-1">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 {{ $scope === $key ? 'active' : '' }}"
                           href="{{ route('remaining.due', [$project, 'scope' => $key]) }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
            <form class="d-flex gap-2" method="get">
                <input type="hidden" name="scope" value="{{ $scope }}">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="عميل، موبايل، عقار…">
                <button class="btn btn-outline-secondary btn-sm">بحث</button>
                <a class="btn btn-outline-success btn-sm text-nowrap" href="{{ route('remaining.due', [$project, 'scope' => $scope, 'q' => request('q'), 'export' => 'csv']) }}">
                    <i class="fa-solid fa-file-csv ms-1"></i> Excel
                </a>
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i></button>
            </form>
        </div>
        <div class="card-body">
            <h5 class="d-none d-print-block mb-3">{{ $project->name }} — الأقساط: {{ $tabs[$scope] }} ({{ now()->format('Y-m-d') }})</h5>
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>العميل</th>
                        <th>الوحدة</th>
                        <th>البند</th>
                        <th>الاستحقاق</th>
                        <th class="text-end">قيمة البند</th>
                        <th class="text-end">المسدد</th>
                        <th class="text-end">المتبقي</th>
                        <th class="text-center">التأخير</th>
                        <th class="text-end d-print-none"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($rows as $r)
                        @php
                            $client = $r['client'];
                            $itemLabel = $r['label'] ?: 'قسط '.$r['number'];
                            $msg = 'السلام عليكم أ/ '.($client?->name ?? '').'، نذكّر حضرتك بـ'.$itemLabel.' بقيمة '.$m($r['balance']).' ج.م'
                                .' المستحق بتاريخ '.$r['due_date']->format('Y-m-d').' عن وحدة '.($r['property']?->name ?? '').' ('.$r['sale']->unitLabel().'). شكرًا لحضرتك.';
                            $wa = \App\Support\Phone::whatsappUrl($client?->phone, $msg);
                        @endphp
                        <tr>
                            <td>
                                @if ($client)
                                    <a href="{{ route('clients.statement', [$project, $client]) }}" class="fw-semibold">{{ $client->name }}</a>
                                    <div class="small text-body-secondary font-monospace">{{ $client->phone }}</div>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $r['property']?->name ?? '—' }}<div class="small text-body-secondary">{{ $r['sale']->unitLabel() }}</div></td>
                            <td>{{ $itemLabel }}</td>
                            <td class="font-monospace">{{ $r['due_date']->format('Y-m-d') }}</td>
                            <td class="text-end font-monospace">{{ $m($r['amount']) }}</td>
                            <td class="text-end font-monospace">{{ $m($r['paid']) }}</td>
                            <td class="text-end font-monospace fw-semibold">{{ $m($r['balance']) }}</td>
                            <td class="text-center">
                                @if ($r['days_late'] > 0)
                                    <span class="badge {{ $r['days_late'] > 30 ? 'text-bg-danger' : 'text-bg-warning' }}">{{ $r['days_late'] }} يوم</span>
                                @else
                                    <span class="badge text-bg-light border">بعد {{ (int) now()->startOfDay()->diffInDays($r['due_date']) }} يوم</span>
                                @endif
                            </td>
                            <td class="text-end text-nowrap d-print-none">
                                @if ($wa)
                                    <a href="{{ $wa }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm" title="تذكير واتساب"><i class="fa-brands fa-whatsapp"></i></a>
                                @endif
                                @can('revenues.create')
                                    <a href="{{ route('revenues.create', [$project, 'contract_id' => $r['sale']->contract?->id]) }}" class="btn btn-outline-primary btn-sm" title="تسجيل تحصيل"><i class="fa-solid fa-hand-holding-dollar"></i></a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-4">مفيش أقساط {{ $scope === 'overdue' ? 'متأخرة' : 'مستحقة في الفترة دي' }}.</td></tr>
                    @endforelse
                    </tbody>
                    @if ($rows->isNotEmpty())
                        <tfoot>
                        <tr class="table-light fw-bold">
                            <td colspan="6">الإجمالي ({{ $rows->count() }} بند)</td>
                            <td class="text-end font-monospace">{{ $m($rows->sum('balance')) }}</td>
                            <td colspan="2"></td>
                        </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
            <p class="small text-body-secondary mb-0">التحصيلات المعتمدة بتتوزع على أقدم قسط الأول. البيعات المعلّقة أو المرفوضة مش داخلة.</p>
        </div>
    </div>
@endsection
