<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'تسجيل الدخول' }}</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body class="bg-body-tertiary d-flex align-items-center min-vh-100">
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5 col-lg-4">
            <div class="card shadow-sm mt-4">
                <div class="card-body p-4">
                    <h1 class="h4 mb-3 text-center">Mohaseb Aqary</h1>
                    <p class="text-muted small text-center mb-4">تسجيل الدخول إلى النظام</p>

                    @if (session('success'))
                        <div class="alert alert-success py-2">{{ session('success') }}</div>
                    @endif

                    <form method="post" action="{{ route('login') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="email">البريد الإلكتروني</label>
                            <input type="email" name="email" id="email" value="{{ old('email') }}" class="form-control @error('email') is-invalid @enderror" required autofocus autocomplete="username">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password">كلمة المرور</label>
                            <input type="password" name="password" id="password" class="form-control" required autocomplete="current-password">
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" name="remember" id="remember" value="1" class="form-check-input">
                            <label class="form-check-label" for="remember">تذكرني</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">دخول</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
