@php
    /** @var \App\Mail\DailyAvailableUnitsReportMail $mail */
    $mail = $this;
@endphp

<div style="font-family: Arial, sans-serif; direction: rtl; text-align: right;">
    <h2 style="margin:0 0 8px;">تقرير الوحدات المتاحة (غير مباعة)</h2>
    <div style="color:#666; margin-bottom:16px;">
        المشروع: <strong>{{ $mail->project->name }}</strong> — التاريخ: <strong>{{ $mail->reportDate }}</strong>
    </div>

    @if (empty($mail->rows))
        <p>لا توجد وحدات متاحة اليوم.</p>
    @else
        <table cellpadding="8" cellspacing="0" border="1" style="border-collapse: collapse; width: 100%;">
            <thead style="background:#f3f4f6;">
                <tr>
                    <th align="right">العقار</th>
                    <th align="center">إجمالي الوحدات</th>
                    <th align="center">مباع (معتمد)</th>
                    <th align="center">متاح</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($mail->rows as $r)
                    <tr>
                        <td>{{ $r['property_name'] }}</td>
                        <td align="center">{{ $r['total_units'] }}</td>
                        <td align="center">{{ $r['sold_units'] }}</td>
                        <td align="center"><strong>{{ $r['available_units'] }}</strong></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="color:#666; margin-top:12px; font-size: 12px;">
            ملاحظة: “المباع” يعتمد على البيعات المعتمدة فقط (Approved). البيعات المعلّقة لا تُخصم من المتاح.
        </p>
    @endif
</div>

