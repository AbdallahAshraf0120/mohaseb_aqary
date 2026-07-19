@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة نوع مصروف</h5>
            <a href="{{ route('expense-types.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('expense-types.store') }}">
                @include('expense-types._form', ['expenseType' => null])
                <button type="submit" class="btn btn-primary mt-3">حفظ</button>
            </form>
        </div>
    </div>
@endsection
