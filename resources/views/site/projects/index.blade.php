@extends('layouts.site')

@section('content')
    <section class="site-page-hero site-page-hero--has-3d">
        <div class="site-3d-stage site-3d-stage--bg site-3d-stage--bg-page" data-building-3d data-variant="twin" data-bg-3d aria-hidden="true"></div>
        <div class="site-wrap">
            <h1>المشاريع</h1>
            <p>استكشف مشاريع {{ $site->brand }} السكنية والمناطق المتاحة داخل كل مشروع.</p>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap">
            @if ($projects->isEmpty())
                <div class="site-empty">لا توجد مشاريع معروضة حالياً.</div>
            @else
                <div class="site-projects">
                    @foreach ($projects as $item)
                        @include('site.partials.project-card', ['item' => $item])
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
