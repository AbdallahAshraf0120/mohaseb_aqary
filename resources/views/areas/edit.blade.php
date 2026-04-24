@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل المنطقة</h5>
            <a href="{{ route('areas.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('areas.update', $area) }}">
                @method('PUT')
                @include('areas._form')
                <button class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
