@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة وجهة</h5>
            <a href="{{ route('facings.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('facings.store') }}">
                @include('facings._form', ['facing' => null])
                <button type="submit" class="btn btn-primary mt-3">حفظ</button>
            </form>
        </div>
    </div>
@endsection
