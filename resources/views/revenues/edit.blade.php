@extends('layouts.admin')

@section('content')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل التحصيل</h5>
            <a href="{{ route('revenues.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('revenues.update', $revenue) }}">
                @method('PUT')
                @include('revenues._form')
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">تحديث التحصيل</button>
                </div>
            </form>
        </div>
    </div>
@endsection
