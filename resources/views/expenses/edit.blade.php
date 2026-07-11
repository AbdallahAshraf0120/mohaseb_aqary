@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل المصروف</h5>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('expenses.update', [$project, $expense]) }}">
                @method('PUT')
                @include('expenses._form')
                <button type="submit" class="btn btn-primary mt-3">تحديث المصروف</button>
            </form>
        </div>
    </div>
@endsection
