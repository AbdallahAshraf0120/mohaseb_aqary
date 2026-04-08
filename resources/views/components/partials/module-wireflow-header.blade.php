@props([
    'label' => '',
    'step' => '-',
    'total' => 13,
    'project' => 'مشروع النخبة ريزيدنس',
    'period' => '2026 Q1',
    'currency' => 'جنيه مصري',
])

<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                <div>
                    <h4 class="mb-1">{{ $label }}</h4>
                    <p class="text-muted mb-0">{{ $project }} | الفترة {{ $period }} | العملة {{ $currency }}</p>
                </div>
                <div class="text-end">
                    <div class="badge text-bg-primary mb-2">الخطوة {{ $step }} من {{ $total }}</div>
                    <div class="small text-muted">Demo Wireflow</div>
                </div>
            </div>
        </div>
    </div>
</div>
