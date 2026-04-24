@extends('layouts.admin')

@section('content')
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">المشاريع المعروضة (تظهر في الشريط الجانبي)</h5>
                </div>
                <div class="card-body p-0">
                    @if ($projects->isEmpty())
                        <p class="p-3 mb-0 text-body-secondary">لا يوجد مشروع نشط بعد. أنشئ مشروعًا جديدًا من اليمين.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($projects as $p)
                                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <strong>{{ $p->name }}</strong>
                                        @if ($p->code)
                                            <span class="text-body-secondary small ms-2">{{ $p->code }}</span>
                                        @endif
                                        <div class="small text-body-secondary mt-1">مسار لوحة التحكم: <code class="user-select-all">/{{ $p->id }}/properties</code></div>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        @if ((int) session('current_project_id') === (int) $p->id)
                                            <span class="badge text-bg-primary">المشروع الحالي</span>
                                        @endif
                                        <a href="{{ route('properties.index', $p) }}" class="btn btn-sm btn-primary">فتح لوحة التحكم</a>
                                        <a href="{{ route('properties.index', $p) }}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">تبويب جديد</a>
                                        <a href="{{ route('projects.edit', $p) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                        <form method="post" action="{{ route('projects.draft', $p) }}" class="d-inline" data-swal-confirm="{{ e('نقل المشروع إلى المسودة؟ سيختفي من الشريط الجانبي إلى أن تستعيده.') }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning">مسودة</button>
                                        </form>
                                        @if ($p->code !== 'default')
                                            <form method="post" action="{{ route('projects.destroy', $p) }}" class="d-inline" data-swal-confirm="{{ e('حذف المشروع نهائيًا مع كل المناطق والعقارات والعقود والبيانات المرتبطة؟ لا يمكن التراجع.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">مشاريع في المسودة</h5>
                </div>
                <div class="card-body p-0">
                    @if ($draftProjects->isEmpty())
                        <p class="p-3 mb-0 text-body-secondary">لا توجد مشاريع في المسودة.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            @foreach ($draftProjects as $p)
                                <li class="list-group-item d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div>
                                        <strong>{{ $p->name }}</strong>
                                        @if ($p->code)
                                            <span class="text-body-secondary small ms-2">{{ $p->code }}</span>
                                        @endif
                                        <span class="badge text-bg-secondary ms-2">مسودة</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1 align-items-center">
                                        <a href="{{ route('projects.edit', $p) }}" class="btn btn-sm btn-outline-primary">تعديل</a>
                                        <form method="post" action="{{ route('projects.restore', $p) }}" class="mb-0">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success">إرجاع للقائمة</button>
                                        </form>
                                        @if ($p->code !== 'default')
                                            <form method="post" action="{{ route('projects.destroy', $p) }}" class="mb-0 d-inline" data-swal-confirm="{{ e('حذف مشروع المسودة نهائيًا مع كل بياناته؟ لا يمكن التراجع.') }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger">حذف</button>
                                            </form>
                                        @endif
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">مشروع جديد</h5>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('projects.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label" for="project-name">اسم المشروع</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="project-name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="project-code">رمز (اختياري)</label>
                            <input type="text" class="form-control @error('code') is-invalid @enderror" id="project-code" name="code" value="{{ old('code') }}">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-primary w-100">إنشاء</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
