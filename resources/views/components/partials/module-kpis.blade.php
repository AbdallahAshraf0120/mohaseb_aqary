@props(['items' => []])

<div class="row g-3 mb-3">
    @forelse ($items as $item)
        <div class="col-lg-3 col-md-6">
            <div class="small-box text-bg-light border">
                <div class="inner">
                    <h5 class="mb-2">{{ $item['value'] ?? '-' }}</h5>
                    <p class="mb-0">{{ $item['label'] ?? '-' }}</p>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-light border mb-0">لا توجد مؤشرات متاحة.</div>
        </div>
    @endforelse
</div>
