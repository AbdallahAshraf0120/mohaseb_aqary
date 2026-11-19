@props(['items' => []])

@php
    $n = max(1, count($items));
    $col = $n >= 4 ? 'col-12 col-sm-6 col-xl-3' : ($n === 3 ? 'col-12 col-sm-6 col-lg-4' : ($n === 2 ? 'col-12 col-sm-6' : 'col-12'));
    $autoIcons = [
        'متأخر' => ['fa-triangle-exclamation', 'danger'], 'مرتد' => ['fa-rotate-left', 'danger'], 'مصروف' => ['fa-arrow-trend-down', 'danger'],
        'المتبقي' => ['fa-hourglass-half', 'warning'], 'مستحق' => ['fa-calendar-day', 'warning'], 'عرابين' => ['fa-hand-holding-dollar', 'warning'],
        'محصل' => ['fa-coins', 'success'], 'المحصلة' => ['fa-coins', 'success'], 'تحصيل' => ['fa-coins', 'success'], 'قبض' => ['fa-arrow-trend-up', 'success'], 'اتصرف' => ['fa-circle-check', 'success'],
        'رصيد' => ['fa-vault', 'primary'], 'المبيعات' => ['fa-file-contract', 'primary'], 'شيكات' => ['fa-money-check', 'primary'],
        'عدد' => ['fa-hashtag', 'neutral'], 'عملاء' => ['fa-users', 'neutral'], 'حجوزات' => ['fa-bookmark', 'primary'],
    ];
@endphp

<div class="row g-3 mb-3">
    @forelse ($items as $item)
        @php
            $label = (string) ($item['label'] ?? '-');
            $value = (string) ($item['value'] ?? '-');
            $unit = null;
            if (str_ends_with($value, ' ج.م')) {
                $value = mb_substr($value, 0, mb_strlen($value) - 4);
                $unit = 'ج.م';
            } elseif (str_starts_with($value, 'ج.م ')) {
                $value = mb_substr($value, 4);
                $unit = 'ج.م';
            }
            [$icon, $tone] = [$item['icon'] ?? null, $item['tone'] ?? null];
            if (! $icon) {
                foreach ($autoIcons as $needle => [$i, $t]) {
                    if (str_contains($label, $needle)) { $icon = $i; $tone = $tone ?? $t; break; }
                }
            }
            $icon = $icon ?: 'fa-chart-simple';
            $tone = $tone ?: 'primary';
            $tag = ! empty($item['url']) ? 'a' : 'div';
        @endphp
        <div class="{{ $col }}">
            <{{ $tag }} @if ($tag === 'a') href="{{ $item['url'] }}" @endif class="ma-kpi tone-{{ $tone }}">
                <span class="ma-kpi-icon"><i class="fa-solid {{ $icon }}"></i></span>
                <div class="min-w-0">
                    <div class="ma-kpi-label">{{ $label }}</div>
                    <div class="ma-kpi-value">{{ $value }} @if ($unit)<small>{{ $unit }}</small>@endif</div>
                    @if (! empty($item['hint']))<div class="ma-kpi-hint">{{ $item['hint'] }}</div>@endif
                </div>
            </{{ $tag }}>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-light border mb-0">لا توجد مؤشرات متاحة.</div>
        </div>
    @endforelse
</div>
