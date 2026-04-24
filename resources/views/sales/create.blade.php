@extends('layouts.admin')

@section('content')
    <div class="row mb-3">
        <div class="col-12">
            <div class="card app-surface mb-4">
                <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        <h4 class="mb-1">تسجيل بيع</h4>
                        <p class="text-muted mb-0">اختيار العقار والدور والنموذج ثم خطة السداد وبيانات العميل</p>
                    </div>
                    <div class="text-end">
                        <div class="badge text-bg-primary mb-2">الخطوة 6 من 13</div>
                        <div class="small text-muted">Demo Wireflow</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card app-surface h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بيانات البيعة</h5>
                    <a href="{{ route('sales.index') }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
                </div>
                <div class="card-body">
                    <form method="post" action="{{ route('sales.store') }}">
                        @include('sales._form')
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">حفظ البيعة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card app-surface h-100">
                <div class="card-header">
                    <h5 class="mb-0">اجراءات سريعة</h5>
                </div>
                <div class="card-body d-flex flex-column gap-2">
                    <button type="button" class="btn btn-outline-secondary text-start">تسجيل بيع</button>
                    <button type="button" class="btn btn-outline-secondary text-start">جدولة اقساط</button>
                    <button type="button" class="btn btn-outline-secondary text-start">توليد إيصال مقدم</button>
                    <hr>
                    <a href="{{ route('sales.index') }}" class="btn btn-primary">رجوع إلى قائمة المبيعات</a>
                </div>
            </div>
        </div>
    </div>
@endsection
