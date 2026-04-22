@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل الوجهة</h5>
            <a href="{{ route('facings.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('facings.update', $facing) }}">
                @method('PUT')
                @include('facings._form', ['facing' => $facing])
                <button type="submit" class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
