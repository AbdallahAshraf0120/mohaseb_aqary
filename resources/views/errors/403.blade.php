@extends('layouts.admin')

@section('content')
    <div class="alert alert-warning">
        <h4 class="alert-heading">غير مصرّح</h4>
        <p class="mb-0">{{ $exception->getMessage() ?: 'ليس لديك صلاحية للوصول إلى هذه الصفحة.' }}</p>
    </div>
    <a href="{{ url()->previous() ?: route('projects.index') }}" class="btn btn-outline-secondary">رجوع</a>
@endsection
