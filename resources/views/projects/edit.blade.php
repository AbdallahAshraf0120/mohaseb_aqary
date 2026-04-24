@extends('layouts.admin')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="card app-surface mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">تعديل المشروع</h5>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">رجوع</a>
                </div>
                <div class="card-body">
                    @if ($project->code === 'default')
                        <div class="alert alert-info small mb-3">
                            هذا المشروع الافتراضي للنظام؛ يبقى رمزه <code>default</code> كما هو ولا يمكن حذفه.
                        </div>
                    @endif
                    <form method="post" action="{{ route('projects.update', $project) }}">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="project-name">اسم المشروع</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="project-name" name="name" value="{{ old('name', $project->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="project-code">رمز (اختياري)</label>
                            @if ($project->code === 'default')
                                <input type="text" class="form-control" id="project-code" value="default" readonly disabled>
                                <input type="hidden" name="code" value="default">
                            @else
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="project-code" name="code" value="{{ old('code', $project->code) }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>
                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
