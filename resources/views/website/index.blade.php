@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        </div>
    @endif

    <div class="card app-surface border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <div class="d-flex flex-wrap align-items-start justify-content-between gap-3 mb-3">
                <div>
                    <h4 class="mb-1 fw-semibold d-flex align-items-center gap-2">
                        <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center" style="width:2.5rem;height:2.5rem">
                            <i class="fa-solid fa-globe"></i>
                        </span>
                        إدارة الموقع الإلكتروني
                    </h4>
                    <p class="text-body-secondary small mb-0">
                        تحكم في محتوى الموقع العام من أقسام منفصلة — الهوية، الرئيسية، من نحن، الثقة، والتواصل.
                    </p>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ url('/') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary btn-sm">
                        <i class="fa-solid fa-arrow-up-right-from-square ms-1"></i> فتح الموقع
                    </a>
                    <span class="badge align-self-center {{ ($site->is_published ?? true) ? 'text-bg-success' : 'text-bg-secondary' }}">
                        {{ ($site->is_published ?? true) ? 'الموقع منشور' : 'الموقع متوقف' }}
                    </span>
                </div>
            </div>

            <div class="rounded-3 border border-primary border-opacity-25 bg-primary bg-opacity-10 p-3 mb-3">
                <form method="post" action="{{ route('website.brand.update') }}" class="row g-2 align-items-end">
                    @csrf
                    @method('PUT')
                    <div class="col-md">
                        <label class="form-label fw-semibold mb-1" for="brand-quick">اسم البراند</label>
                        <p class="small text-body-secondary mb-2">يظهر في الهيرو، القائمة، شاشة الافتتاح، والفوتر على الموقع العام.</p>
                        <input
                            type="text"
                            name="brand"
                            id="brand-quick"
                            class="form-control form-control-lg @error('brand') is-invalid @enderror"
                            value="{{ old('brand', $site->brand) }}"
                            required
                            maxlength="120"
                            autocomplete="organization"
                            placeholder="مثال: المنارة"
                        >
                        @error('brand')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-md-auto">
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="fa-solid fa-floppy-disk ms-1"></i> حفظ البراند
                        </button>
                    </div>
                </form>
            </div>

            <div class="row g-3">
                <div class="col-sm-6 col-xl-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1">البراند الحالي</div>
                        <div class="fw-semibold fs-5">{{ $site->brand }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1">الهاتف</div>
                        <div class="fw-semibold">{{ $site->phone ?: '—' }}</div>
                    </div>
                </div>
                <div class="col-sm-6 col-xl-4">
                    <div class="rounded-3 border bg-body-tertiary bg-opacity-40 p-3 h-100">
                        <div class="small text-body-secondary mb-1">واتساب</div>
                        <div class="fw-semibold">{{ $site->whatsapp ?: '—' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        @foreach ($sections as $key => $meta)
            <div class="col-md-6 col-xl-4">
                <a href="{{ $meta['route'] ?? route('website.edit', $key) }}" class="card app-surface border-0 shadow-sm h-100 text-decoration-none text-body">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-start gap-3">
                            <span class="rounded-circle bg-primary bg-opacity-10 text-primary d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width:2.75rem;height:2.75rem">
                                <i class="fa-solid {{ $meta['icon'] }}"></i>
                            </span>
                            <div>
                                <h5 class="mb-1 fw-semibold">{{ $meta['title'] }}</h5>
                                <p class="text-body-secondary small mb-0">{{ $meta['desc'] }}</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
    </div>
@endsection
