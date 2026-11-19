@php
    $company = in_array($companyName, ['Real Estate Demo', ''], true) ? $project->name : $companyName;
    $amountWords = \App\Support\ArabicNumber::money($r['amount']);
    $m = fn ($v) => \App\Support\Money::display((float) $v);
    $sale = $r['sale'];
@endphp
<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إيصال {{ $r['number'] }} — {{ $company }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: "Cairo", "Segoe UI", Tahoma, Arial, sans-serif; color: #111; margin: 0; background: #f3f4f6; }
        .toolbar { max-width: 190mm; margin: 12px auto; display: flex; gap: 8px; justify-content: flex-start; }
        .toolbar button, .toolbar a { padding: 8px 16px; border-radius: 8px; border: 1px solid #94a3b8; background: #fff; color: #111; font: inherit; cursor: pointer; text-decoration: none; }
        .toolbar button { background: #1d4ed8; color: #fff; border-color: #1d4ed8; }
        .sheet { max-width: 190mm; margin: 0 auto; background: #fff; }
        .receipt { border: 1.5px solid #111; border-radius: 6px; padding: 10mm 10mm 8mm; height: 132mm; position: relative; overflow: hidden; }
        .receipt + .cut { border-top: 1px dashed #999; margin: 6mm 0; position: relative; text-align: center; font-size: 11px; color: #777; }
        .head { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #111; padding-bottom: 4mm; margin-bottom: 5mm; }
        .company { font-size: 20px; font-weight: 800; }
        .project { font-size: 13px; color: #444; }
        .title { text-align: center; }
        .title h1 { margin: 0; font-size: 22px; letter-spacing: 1px; }
        .title .copy { font-size: 11px; color: #555; }
        .no { text-align: left; font-size: 13px; }
        .no b { font-size: 16px; font-family: monospace; }
        .row { display: flex; gap: 6mm; margin-bottom: 4mm; font-size: 14px; }
        .row .lbl { color: #444; min-width: 32mm; }
        .row .val { flex: 1; border-bottom: 1px dotted #888; font-weight: 600; padding-bottom: 1mm; }
        .amount-box { display: flex; gap: 4mm; align-items: center; margin: 5mm 0; }
        .amount { border: 2px solid #111; border-radius: 6px; padding: 3mm 6mm; font-size: 20px; font-weight: 800; font-family: monospace; white-space: nowrap; }
        .words { flex: 1; font-size: 14px; font-weight: 600; background: #f3f4f6; padding: 3mm 4mm; border-radius: 4px; }
        .sign { display: flex; justify-content: space-between; position: absolute; bottom: 8mm; left: 10mm; right: 10mm; font-size: 13px; }
        .sign div { width: 45%; border-top: 1px solid #111; padding-top: 2mm; text-align: center; }
        .stamp { position: absolute; top: 45%; left: 50%; transform: translate(-50%, -50%) rotate(-18deg); font-size: 44px; color: rgba(220, 38, 38, .18); font-weight: 900; border: 5px solid rgba(220, 38, 38, .18); padding: 2mm 8mm; border-radius: 8px; pointer-events: none; }
        @media print {
            body { background: #fff; }
            .toolbar { display: none; }
            .sheet { max-width: none; }
        }
    </style>
</head>
<body>
<div class="toolbar">
    <button onclick="window.print()">طباعة</button>
    <a href="javascript:history.back()">رجوع</a>
</div>
<div class="sheet">
    @foreach (['الأصل — للعميل', 'صورة — للحسابات'] as $copyLabel)
        <div class="receipt">
            @if ($r['pending'])
                <div class="stamp">مبدئي — لم يُعتمد</div>
            @endif
            <div class="head">
                <div>
                    <div class="company">{{ $company }}</div>
                    <div class="project">{{ $project->name }}</div>
                </div>
                <div class="title">
                    <h1>إيصال استلام نقدية</h1>
                    <div class="copy">{{ $copyLabel }}</div>
                </div>
                <div class="no">
                    رقم: <b>{{ $r['number'] }}</b><br>
                    التاريخ: <b>{{ optional($r['date'])->format('Y-m-d') }}</b>
                </div>
            </div>

            <div class="row"><span class="lbl">استلمنا من السيد/</span><span class="val">{{ $r['client']?->name ?? '—' }}</span></div>
            <div class="amount-box">
                <div class="amount">{{ $m($r['amount']) }} ج.م</div>
                <div class="words">{{ $amountWords }}</div>
            </div>
            <div class="row"><span class="lbl">وذلك قيمة</span><span class="val">{{ $r['purpose'] }}@if ($sale) — {{ $sale->property?->name }} ({{ $sale->unitLabel() }})@endif</span></div>
            <div class="row">
                <span class="lbl">طريقة الدفع</span>
                <span class="val">
                    {{ $r['method'] }}
                    @if ($r['cheque'])
                        — شيك رقم {{ $r['cheque']->cheque_number }} {{ $r['cheque']->cheque_bank ? '/ '.$r['cheque']->cheque_bank : '' }}
                        يستحق {{ optional($r['cheque']->cheque_due_date)->format('Y-m-d') }}
                    @endif
                </span>
            </div>
            @if ($r['remaining'] !== null)
                <div class="row"><span class="lbl">المتبقي على الوحدة</span><span class="val">{{ $m($r['remaining']) }} ج.م</span></div>
            @endif
            @if ($r['cheque'])
                <div class="row" style="font-size:12px;color:#555">الشيك تحت التحصيل، ولا تُعتبر القيمة مسددة إلا بعد صرفه.</div>
            @endif

            <div class="sign">
                <div>المستلم / الخزينة</div>
                <div>توقيع العميل</div>
            </div>
        </div>
        @if ($loop->first)
            <div class="cut">✂ — — — — — — — — — — — — — — — — — — — — — —</div>
        @endif
    @endforeach
</div>
</body>
</html>
