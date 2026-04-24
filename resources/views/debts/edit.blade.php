@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل ذمة دائنة</h5>
            <a href="{{ route('debts.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('debts.update', [$project, $debt]) }}">
                @method('PUT')
                @include('debts._form')
                <button type="submit" class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
