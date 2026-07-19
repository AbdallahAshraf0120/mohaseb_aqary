@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل نوع المصروف</h5>
            <a href="{{ route('expense-types.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('expense-types.update', $expenseType) }}">
                @method('PUT')
                @include('expense-types._form', ['expenseType' => $expenseType])
                <button type="submit" class="btn btn-primary mt-3">تحديث</button>
            </form>
        </div>
    </div>
@endsection
