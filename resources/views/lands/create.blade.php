@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة أرض</h5>
            <a href="{{ route('lands.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('lands.store', $project) }}">
                @include('lands._form')
                <button class="btn btn-primary mt-3">حفظ</button>
            </form>
        </div>
    </div>
@endsection
