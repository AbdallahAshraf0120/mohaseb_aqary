@extends('layouts.admin')

@section('content')
    @section('page-actions')
        <a href="{{ route('sales.index') }}" class="btn btn-light"><i class="fa-solid fa-arrow-right ms-1"></i> رجوع للمبيعات</a>
    @endsection

    <form method="post" action="{{ route('sales.store') }}" class="ma-form-page">
        <div class="card app-surface mb-3">
            <div class="card-header"><h5>بيانات البيعة</h5></div>
            <div class="card-body">
                @include('sales._form')
            </div>
        </div>
        <div class="ma-form-actions">
            <a href="{{ route('sales.index') }}" class="btn btn-light">إلغاء</a>
            <button type="submit" class="btn btn-primary px-4"><i class="fa-solid fa-check ms-1"></i> حفظ البيعة</button>
        </div>
    </form>
@endsection
