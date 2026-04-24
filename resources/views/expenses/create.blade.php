@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">إضافة مصروف</h5>
            <a href="{{ route('expenses.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('expenses.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">الفئة</label>
                        <input name="category" class="form-control" required value="{{ old('category') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">القيمة</label>
                        <input type="number" step="0.01" min="1" name="amount" class="form-control" required value="{{ old('amount') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">الوصف</label>
                        <input name="description" class="form-control" value="{{ old('description') }}">
                    </div>
                </div>
                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <button class="btn btn-primary mt-3">حفظ</button>
            </form>
        </div>
    </div>
@endsection
