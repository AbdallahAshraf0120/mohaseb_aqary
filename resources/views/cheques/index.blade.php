@extends('layouts.admin')

@section('content')
    @php
        $m = fn ($v) => \App\Support\Money::display((float) $v);
        $tabs = ['open' => 'لسه ما اتصرفتش', 'due' => 'مستحقة خلال أسبوع', 'cleared' => 'اتصرفت', 'bounced' => 'مرتدة', 'all' => 'الكل'];
        $badge = ['in_hand' => 'text-bg-secondary', 'deposited' => 'text-bg-info', 'cleared' => 'text-bg-success', 'bounced' => 'text-bg-danger'];
    @endphp

    <x-partials.module-kpis :items="[
        ['label' => 'شيكات في الخزنة', 'value' => $m($kpis['in_hand']) . ' ج.م'],
        ['label' => 'اتودعت البنك', 'value' => $m($kpis['deposited']) . ' ج.م'],
        ['label' => 'مستحقة خلال أسبوع', 'value' => $m($kpis['due_week']) . ' ج.م'],
        ['label' => 'مرتدة', 'value' => $m($kpis['bounced']) . ' ج.م'],
    ]" />

    <div class="card app-surface mb-4">
        <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
            <ul class="nav nav-pills gap-1">
                @foreach ($tabs as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link py-1 px-3 {{ $status === $key ? 'active' : '' }}" href="{{ route('cheques.index', [$project, 'status' => $key]) }}">{{ $label }}</a>
                    </li>
                @endforeach
            </ul>
            <form class="d-flex gap-2" method="get">
                <input type="hidden" name="status" value="{{ $status }}">
                <input type="search" name="q" value="{{ request('q') }}" class="form-control form-control-sm" placeholder="رقم الشيك، البنك، العميل…">
                <button class="btn btn-outline-secondary btn-sm">بحث</button>
            </form>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead>
                    <tr>
                        <th>رقم الشيك</th>
                        <th>البنك</th>
                        <th>العميل</th>
                        <th>الوحدة</th>
                        <th>الاستحقاق</th>
                        <th class="text-end">المبلغ</th>
                        <th>الحالة</th>
                        <th class="text-end">تغيير الحالة</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($cheques as $c)
                        @php $late = $c->cheque_due_date && $c->cheque_due_date->lt(now()->startOfDay()) && $c->isUnclearedCheque(); @endphp
                        <tr>
                            <td class="font-monospace">{{ $c->cheque_number ?: '—' }}
                                <div class="small text-body-secondary">{{ $c->receiptNumber() }}</div></td>
                            <td>{{ $c->cheque_bank ?: '—' }}</td>
                            <td>{{ $c->client?->name ?? '—' }}<div class="small text-body-secondary font-monospace">{{ $c->client?->phone }}</div></td>
                            <td>{{ $c->sale?->property?->name ?? '—' }}<div class="small text-body-secondary">{{ $c->sale?->unitLabel() }}</div></td>
                            <td class="font-monospace {{ $late ? 'text-danger fw-semibold' : '' }}">{{ optional($c->cheque_due_date)->format('Y-m-d') ?? '—' }}</td>
                            <td class="text-end font-monospace">{{ $m($c->amount) }}</td>
                            <td>
                                <span class="badge {{ $badge[$c->cheque_status] ?? 'text-bg-light' }}">{{ $c->chequeStatusLabel() }}</span>
                                @if ($c->approval_status === 'pending')<span class="badge text-bg-warning">تحصيل معلّق</span>@endif
                            </td>
                            <td class="text-end">
                                @can('revenues.update')
                                    <form method="post" action="{{ route('cheques.status', [$project, $c]) }}" class="d-inline-flex gap-1">
                                        @csrf
                                        @foreach (['deposited' => ['اتودع', 'btn-outline-info'], 'cleared' => ['اتصرف', 'btn-outline-success'], 'bounced' => ['ارتد', 'btn-outline-danger'], 'in_hand' => ['رجّعه للخزنة', 'btn-outline-secondary']] as $st => [$lbl, $cls])
                                            @continue($st === $c->cheque_status)
                                            @continue($st === 'in_hand' && $c->cheque_status === 'in_hand')
                                            <button name="cheque_status" value="{{ $st }}" class="btn btn-sm {{ $cls }}"
                                                    @if ($st === 'bounced') onclick="return confirm('تأكيد إن الشيك ارتد؟ المبلغ هيرجع على العميل.')" @endif>{{ $lbl }}</button>
                                        @endforeach
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="text-center text-muted py-4">مفيش شيكات هنا.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <div>{{ $cheques->links() }}</div>
            <p class="small text-body-secondary mb-0">الشيك بيدخل الصندوق ويقلّل المتبقي على العميل لما يتعلَّم «اتصرف» بس. الشيك المرتد بيفضل مسجّل للمتابعة والمبلغ بيفضل على العميل.</p>
        </div>
    </div>
@endsection
