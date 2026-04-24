@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة ذمة دائنة (مورد)</h5>
            <a href="{{ route('debts.index', $project) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <p class="small text-body-secondary mb-3">سجّل هنا شراءً للمشروع من مورد ولم يُسدَّد ثمنه بالكامل بعد.</p>
            <form method="post" action="{{ route('debts.store', $project) }}">
                @include('debts._form')
                <button type="submit" class="btn btn-primary mt-3">حفظ</button>
            </form>
        </div>
    </div>
@endsection
