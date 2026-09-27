@extends('layouts.site')

@section('content')
    <section class="site-page-hero">
        <div class="site-wrap site-reveal site-reveal--scale" style="text-align:center;max-width:34rem;margin-inline:auto;padding:4rem 0">
            <h1 style="margin-bottom:.75rem">{{ $site->brand }}</h1>
            <p style="margin:0;color:var(--site-ink-soft)">الموقع قيد التحديث حالياً. نعود قريباً.</p>
        </div>
    </section>
@endsection
