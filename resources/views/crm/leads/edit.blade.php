@extends('layouts.admin')

@section('content')
    <div class="card app-surface mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">تعديل عميل محتمل</h5>
            <a href="{{ route('crm-leads.show', $lead) }}" class="btn btn-outline-secondary btn-sm">رجوع</a>
        </div>
        <div class="card-body">
            <form method="post" action="{{ route('crm-leads.update', $lead) }}">
                @csrf
                @method('PUT')
                @include('crm.leads._form', ['lead' => $lead])
                <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary">حفظ</button>
                    <a href="{{ route('crm-leads.show', $lead) }}" class="btn btn-outline-secondary">إلغاء</a>
                </div>
            </form>
        </div>
    </div>
@endsection

