<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 10pt; color: #222; }
        h1 { font-size: 16pt; margin: 0 0 8px 0; }
        h2 { font-size: 12pt; margin: 18px 0 8px 0; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .muted { color: #555; font-size: 9pt; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; page-break-inside: auto; }
        tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #bbb; padding: 4px 6px; text-align: right; vertical-align: top; }
        th { background: #f0f0f0; font-weight: bold; }
        .num { direction: ltr; unicode-bidi: isolate; text-align: left; font-family: monospace; }
        .summary-grid td:first-child { font-weight: 600; width: 42%; background: #fafafa; }
    </style>
</head>
<body>

<h1>تقرير المشروع — {{ $project->name }}</h1>
<p class="muted">
    Mohaseb Aqary — الفترة: {{ $fromStr }} → {{ $toStr }}
    @if ($filters->q !== '')
        — بحث: {{ $filters->q }}
    @endif
    — العملة: {{ $currencyLabel }}
</p>

<h2>ملخص الفترة</h2>
<table class="summary-grid">
    @foreach ($summaryPeriod as $label => $value)
        <tr>
            <td>{{ $label }}</td>
            <td class="num">{{ is_numeric($value) ? number_format((float) $value, \Illuminate\Support\Str::contains((string) $label, 'عدد') ? 0 : 2, '.', ',') : $value }}</td>
        </tr>
    @endforeach
</table>

<h3 style="font-size:12pt;margin:14px 0 6px;">إجماليات المشروع (كل الفترات)</h3>
<table class="summary-grid">
    @foreach ($summaryAllTime as $label => $value)
        <tr>
            <td>{{ $label }}</td>
            <td class="num">{{ is_numeric($value) ? number_format((float) $value, \Illuminate\Support\Str::contains((string) $label, 'عدد') ? 0 : 2, '.', ',') : $value }}</td>
        </tr>
    @endforeach
</table>

<h2>تحصيلات التفصيل</h2>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>التاريخ</th>
        <th>المبلغ</th>
        <th>العميل</th>
        <th>التصنيف</th>
        <th>طريقة الدفع</th>
        <th>ملاحظات</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($revenues as $r)
        <tr>
            <td class="num">{{ $r->id }}</td>
            <td class="num">{{ $r->paid_at?->format('Y-m-d') }}</td>
            <td class="num">{{ number_format((float) $r->amount, 2, '.', ',') }}</td>
            <td>{{ $r->client?->name }}</td>
            <td>{{ $r->category }}</td>
            <td>{{ $r->payment_method }}</td>
            <td>{{ \Illuminate\Support\Str::limit((string) ($r->notes ?? ''), 120) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<pagebreak />

<h2>مصروفات التفصيل</h2>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>التاريخ</th>
        <th>المبلغ</th>
        <th>الفئة</th>
        <th>الوصف</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($expenses as $e)
        <tr>
            <td class="num">{{ $e->id }}</td>
            <td class="num">{{ $e->created_at?->format('Y-m-d H:i') }}</td>
            <td class="num">{{ number_format((float) $e->amount, 2, '.', ',') }}</td>
            <td>{{ $e->category }}</td>
            <td>{{ \Illuminate\Support\Str::limit((string) ($e->description ?? ''), 160) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<pagebreak />

<h2>مبيعات الفترة</h2>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>تاريخ البيع</th>
        <th>العقار</th>
        <th>العميل</th>
        <th>السعر</th>
        <th>المقدم</th>
        <th>النوع</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($sales as $s)
        <tr>
            <td class="num">{{ $s->id }}</td>
            <td class="num">{{ $s->sale_date?->format('Y-m-d') }}</td>
            <td>{{ $s->property?->name }}</td>
            <td>{{ $s->client?->name }}</td>
            <td class="num">{{ number_format((float) $s->sale_price, 2, '.', ',') }}</td>
            <td class="num">{{ number_format((float) $s->down_payment, 2, '.', ',') }}</td>
            <td>{{ $s->payment_type }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<pagebreak />

<h2>صندوق — وارد ثم صادر</h2>
<h3 style="font-size:11pt;margin:10px 0 4px;">وارد</h3>
<table>
    <thead><tr><th>#</th><th>التاريخ</th><th>المبلغ</th><th>الوصف</th></tr></thead>
    <tbody>
    @foreach ($treasuryIn as $t)
        <tr>
            <td class="num">{{ $t->id }}</td>
            <td class="num">{{ $t->created_at?->format('Y-m-d H:i') }}</td>
            <td class="num">{{ number_format((float) $t->amount, 2, '.', ',') }}</td>
            <td>{{ \Illuminate\Support\Str::limit((string) ($t->description ?? ''), 140) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<h3 style="font-size:11pt;margin:14px 0 4px;">صادر</h3>
<table>
    <thead><tr><th>#</th><th>التاريخ</th><th>المبلغ</th><th>الوصف</th></tr></thead>
    <tbody>
    @foreach ($treasuryOut as $t)
        <tr>
            <td class="num">{{ $t->id }}</td>
            <td class="num">{{ $t->created_at?->format('Y-m-d H:i') }}</td>
            <td class="num">{{ number_format((float) $t->amount, 2, '.', ',') }}</td>
            <td>{{ \Illuminate\Support\Str::limit((string) ($t->description ?? ''), 140) }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

<pagebreak />

<h2>العقود</h2>
<table>
    <thead>
    <tr>
        <th>#</th>
        <th>العميل</th>
        <th>العقار</th>
        <th>الإجمالي</th>
        <th>المدفوع</th>
        <th>المتبقي</th>
        <th>البداية</th>
        <th>النهاية</th>
    </tr>
    </thead>
    <tbody>
    @foreach ($contracts as $c)
        <tr>
            <td class="num">{{ $c->id }}</td>
            <td>{{ $c->client?->name }}</td>
            <td>{{ $c->property?->name }}</td>
            <td class="num">{{ number_format((float) $c->total_price, 2, '.', ',') }}</td>
            <td class="num">{{ number_format((float) $c->paid_amount, 2, '.', ',') }}</td>
            <td class="num">{{ number_format((float) $c->remaining_amount, 2, '.', ',') }}</td>
            <td class="num">{{ $c->start_date?->format('Y-m-d') }}</td>
            <td class="num">{{ $c->end_date?->format('Y-m-d') }}</td>
        </tr>
    @endforeach
    </tbody>
</table>

</body>
</html>
