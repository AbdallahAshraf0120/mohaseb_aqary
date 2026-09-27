@extends('layouts.site')

@section('content')
    <section class="site-page-hero site-page-hero--has-3d">
        <div class="site-3d-stage site-3d-stage--bg site-3d-stage--bg-page" data-building-3d data-variant="complex" data-bg-3d aria-hidden="true"></div>
        <div class="site-wrap">
            <h1>{{ $site->contact_title }}</h1>
            <p>{{ $site->contact_intro }}</p>
        </div>
    </section>

    <section class="site-section">
        <div class="site-wrap">
            @if (session('success'))
                <div class="site-alert site-alert--ok">{{ session('success') }}</div>
            @endif

            @if ($errors->any())
                <div class="site-alert site-alert--err">
                    يرجى مراجعة الحقول المطلوبة.
                </div>
            @endif

            <form method="post" action="{{ route('site.contact.store') }}" class="site-form site-reveal">
                @csrf

                <div class="site-field">
                    <label for="name">الاسم</label>
                    <input type="text" name="name" id="name" value="{{ old('name') }}" required autocomplete="name">
                    @error('name')<div class="site-error">{{ $message }}</div>@enderror
                </div>

                <div class="site-field">
                    <label for="phone">رقم الهاتف</label>
                    <input type="tel" name="phone" id="phone" value="{{ old('phone') }}" required autocomplete="tel">
                    @error('phone')<div class="site-error">{{ $message }}</div>@enderror
                </div>

                <div class="site-field">
                    <label for="email">البريد الإلكتروني (اختياري)</label>
                    <input type="email" name="email" id="email" value="{{ old('email') }}" autocomplete="email">
                    @error('email')<div class="site-error">{{ $message }}</div>@enderror
                </div>

                <div class="site-field">
                    <label for="project_id">المشروع المهتم به (اختياري)</label>
                    <select name="project_id" id="project_id">
                        <option value="">— بدون تحديد —</option>
                        @foreach ($projects as $item)
                            <option value="{{ $item->project_id }}" @selected((string) old('project_id', request('project')) === (string) $item->project_id)>
                                {{ $item->displayTitle() }}
                            </option>
                        @endforeach
                    </select>
                    @error('project_id')<div class="site-error">{{ $message }}</div>@enderror
                </div>

                <div class="site-field">
                    <label for="message">رسالتك (اختياري)</label>
                    <textarea name="message" id="message" placeholder="مثال: أبحث عن شقة 3 غرف...">{{ old('message') }}</textarea>
                    @error('message')<div class="site-error">{{ $message }}</div>@enderror
                </div>

                <button type="submit" class="site-btn site-btn--primary">إرسال الطلب</button>
            </form>

            @if ($site->whatsapp || $site->phone)
                <div class="site-reveal" style="margin-top:2rem;color:var(--site-ink-soft)">
                    أو تواصل مباشرة:
                    @if ($site->whatsapp)
                        <a href="https://wa.me/{{ preg_replace('/\D+/', '', $site->whatsapp) }}" style="color:var(--site-accent);font-weight:600;margin-inline-start:.35rem">واتساب</a>
                    @endif
                    @if ($site->phone)
                        <a href="tel:{{ $site->phone }}" style="color:var(--site-accent);font-weight:600;margin-inline-start:.75rem">{{ $site->phone }}</a>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
