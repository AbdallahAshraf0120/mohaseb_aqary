@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل الأرض</h5>
            <a href="{{ route('lands.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('lands.update', $land) }}">
                @method('PUT')
                @include('lands._form')
                <button class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
