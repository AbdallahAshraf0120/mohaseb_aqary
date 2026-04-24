@props([
    'placeholder' => 'بحث…',
    'help' => null,
])

<div {{ $attributes->class(['card border-0 shadow-sm rounded-4 mb-4 overflow-hidden']) }}>
    <div class="card-header bg-body-secondary border-0 py-3 px-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2">
            <div class="fw-semibold"><i class="fa-solid fa-magnifying-glass ms-1 text-body-secondary"></i> بحث وتصفية بالفترة</div>
            @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to'))
                <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-secondary">مسح الفلاتر</a>
            @endif
        </div>
    </div>
    <div class="card-body p-4">
        <form method="get" action="{{ url()->current() }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small text-body-secondary mb-1">نص البحث</label>
                <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $placeholder }}" maxlength="200" autocomplete="off">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">من تاريخ</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control">
            </div>
            <div class="col-md-3">
                <label class="form-label small text-body-secondary mb-1">إلى تاريخ</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100">تطبيق</button>
            </div>
        </form>
        @if ($help)
            <p class="small text-body-secondary mb-0 mt-3">{{ $help }}</p>
        @endif
    </div>
</div>
