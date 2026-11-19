@php $m = fn ($v) => \App\Support\Money::display((float) $v); @endphp
<div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;color:#111;max-width:760px">
    <h2 style="margin:0 0 4px">تذكير الأقساط — {{ $project->name }}</h2>
    <div style="color:#555;margin-bottom:16px">{{ $reportDate }}</div>

    @foreach ([['متأخرات', $overdue, '#b91c1c'], ['مستحق خلال '.$daysBefore.' أيام', $upcoming, '#1d4ed8']] as [$title, $rows, $color])
        <h3 style="color:{{ $color }};margin:16px 0 6px">{{ $title }} ({{ count($rows) }}) — {{ $m(collect($rows)->sum('balance')) }} ج.م</h3>
        @if (count($rows))
            <table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:13px">
                <tr style="background:#f1f5f9"><th align="right">العميل</th><th align="right">الموبايل</th><th align="right">الوحدة</th><th align="right">الاستحقاق</th><th align="left">المتبقي</th><th align="right">تأخير</th><th></th></tr>
                @foreach ($rows as $r)
                    <tr style="border-bottom:1px solid #e5e7eb">
                        <td>{{ $r['client'] }}</td>
                        <td style="font-family:monospace">{{ $r['phone'] }}</td>
                        <td>{{ $r['unit'] }}</td>
                        <td style="font-family:monospace">{{ $r['due_date'] }}</td>
                        <td align="left" style="font-family:monospace">{{ $m($r['balance']) }}</td>
                        <td>{{ $r['days_late'] > 0 ? $r['days_late'].' يوم' : '—' }}</td>
                        <td>@if ($r['wa'])<a href="{{ $r['wa'] }}">واتساب</a>@endif</td>
                    </tr>
                @endforeach
            </table>
        @else
            <div style="color:#555">لا يوجد.</div>
        @endif
    @endforeach

    @if (count($cheques))
        <h3 style="margin:16px 0 6px">شيكات مستحقة الإيداع ({{ count($cheques) }})</h3>
        <table cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;font-size:13px">
            <tr style="background:#f1f5f9"><th align="right">رقم الشيك</th><th align="right">البنك</th><th align="right">العميل</th><th align="right">الاستحقاق</th><th align="left">المبلغ</th></tr>
            @foreach ($cheques as $c)
                <tr style="border-bottom:1px solid #e5e7eb"><td>{{ $c['number'] }}</td><td>{{ $c['bank'] }}</td><td>{{ $c['client'] }}</td><td style="font-family:monospace">{{ $c['due_date'] }}</td><td align="left" style="font-family:monospace">{{ $m($c['amount']) }}</td></tr>
            @endforeach
        </table>
    @endif
</div>
