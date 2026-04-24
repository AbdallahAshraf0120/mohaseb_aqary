@extends('layouts.admin')

@section('content')
    <x-partials.module-wireflow-header label="الإعدادات" step="13" />
    <div class="card app-surface mb-4">
        <div class="card-header"><h5 class="mb-0">الإعدادات العامة</h5></div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            <form method="post" action="{{ route('settings.update') }}">
                @csrf
                @method('PUT')
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">اسم الشركة</label>
                        <input name="company_name" class="form-control" required value="{{ old('company_name', $setting->company_name) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">العملة</label>
                        <input name="currency" class="form-control" required value="{{ old('currency', $setting->currency) }}">
                    </div>
                </div>
                @if ($errors->any())
                    <div class="alert alert-danger mt-3 mb-0">
                        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <button class="btn btn-primary mt-3">حفظ الإعدادات</button>
            </form>
        </div>
    </div>
@endsection
