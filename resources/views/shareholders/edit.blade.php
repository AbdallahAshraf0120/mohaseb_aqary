@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل المساهم</h5>
            <div class="d-flex gap-2">
                <a href="{{ route('shareholders.show', [$project, $shareholder]) }}" class="btn btn-outline-info btn-sm">البروفايل</a>
                <a href="{{ route('shareholders.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
            </div>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('shareholders.update', [$project, $shareholder]) }}">
                @method('PUT')
                @include('shareholders._form')
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">تحديث</button>
                </div>
            </form>
        </div>
    </div>
@endsection
