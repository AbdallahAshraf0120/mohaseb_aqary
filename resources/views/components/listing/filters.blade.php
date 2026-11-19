@props([
    'placeholder' => 'بحث…',
    'help' => null,
    'dates' => true,
])

<form method="get" action="{{ url()->current() }}" {{ $attributes->class(['ma-filterbar']) }}>
    @foreach (request()->except(['q', 'date_from', 'date_to', 'page']) as $k => $v)
        @if (is_scalar($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
    @endforeach
    <div class="ma-search">
        <i class="fa-solid fa-magnifying-glass"></i>
        <input type="search" name="q" value="{{ request('q') }}" class="form-control" placeholder="{{ $placeholder }}" maxlength="200" autocomplete="off">
    </div>
    @if ($dates)
        <div class="ma-date">
            <span>من</span>
            <input type="date" name="date_from" value="{{ request('date_from') }}" class="form-control" aria-label="من تاريخ">
        </div>
        <div class="ma-date">
            <span>إلى</span>
            <input type="date" name="date_to" value="{{ request('date_to') }}" class="form-control" aria-label="إلى تاريخ">
        </div>
    @endif
    <button type="submit" class="btn btn-primary px-3"><i class="fa-solid fa-filter ms-1"></i> تطبيق</button>
    @if (request()->filled('q') || request()->filled('date_from') || request()->filled('date_to'))
        <a href="{{ url()->current() }}" class="btn btn-light">مسح</a>
    @endif
</form>
@if ($help)
    <p class="ma-filterbar-help"><i class="fa-regular fa-circle-question ms-1"></i>{{ $help }}</p>
@endif
