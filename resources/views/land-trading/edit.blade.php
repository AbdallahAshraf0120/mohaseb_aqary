@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل أرض</h5>
            <a href="{{ route('land-trading.show', $parcel) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('land-trading.update', $parcel) }}">
                @csrf
                @method('PUT')
                @include('land-trading._form')
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary">حفظ التعديلات</button>
                    <a href="{{ route('land-trading.show', $parcel) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection
