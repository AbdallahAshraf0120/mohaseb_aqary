@extends('layouts.admin')

@section('content')
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card app-surface mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">تعديل المشروع</h5>
                    <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-secondary">رجوع</a>
                </div>
                <div class="card-body">
                    @if (session('error'))
                        <div class="alert alert-danger small">{{ session('error') }}</div>
                    @endif
                    @if ($project->code === 'default')
                        <div class="alert alert-info small mb-3">
                            هذا المشروع الافتراضي للنظام؛ يبقى رمزه <code>default</code> كما هو ولا يمكن حذفه.
                        </div>
                    @endif
                    <form method="post" action="{{ route('projects.update', $project) }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        <div class="mb-3">
                            <label class="form-label" for="project-name">اسم المشروع</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="project-name" name="name" value="{{ old('name', $project->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="project-code">رمز (اختياري)</label>
                            @if ($project->code === 'default')
                                <input type="text" class="form-control" id="project-code" value="default" readonly disabled>
                                <input type="hidden" name="code" value="default">
                            @else
                                <input type="text" class="form-control @error('code') is-invalid @enderror" id="project-code" name="code" value="{{ old('code', $project->code) }}">
                                @error('code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <hr class="my-4">
                        <h6 class="fw-semibold mb-2">قالب عقد المشروع (Word)</h6>
                        <p class="text-body-secondary small mb-3">
                            يُخزَّن ملف ‎.docx‎ واحد لكل مشروع. من صفحة أي عقد يمكن تنزيل نسخة مملوءة بالبيانات إذا وُجدت عبارات في المستند بنفس أسماء المتغيرات أدناه (انسخها كما هي في Word)، مثل <code dir="ltr">${client_name}</code>.
                        </p>
                        <ul class="small text-body-secondary mb-3 font-monospace" dir="ltr">
                            <li>contract_number, project_name, client_name, client_phone, client_email, client_national_id</li>
                            <li>property_name, sale_price, total_price, down_payment, net_after_down, paid_amount, remaining_amount</li>
                            <li>start_date, end_date, sale_date, payment_type, installment_months</li>
                            <li>broker_name, floor_number, floor_label, apartment_model, sale_notes</li>
                        </ul>
                        @if ($project->hasContractTemplate())
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                                <span class="badge text-bg-success">قالب مرفوع</span>
                                <a href="{{ route('projects.contract-template', $project) }}" class="btn btn-sm btn-outline-primary">تنزيل القالب الحالي</a>
                            </div>
                        @else
                            <p class="small text-warning-emphasis mb-3">لا يوجد قالب بعد؛ سيتعذّر «تصدير عقد Word» من صفحات العقود حتى ترفع ملفًا.</p>
                        @endif
                        <div class="mb-3">
                            <label class="form-label" for="contract-template">رفع قالب جديد ‎(.docx)‎</label>
                            <input type="file" class="form-control @error('contract_template') is-invalid @enderror" id="contract-template" name="contract_template" accept=".docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document">
                            @error('contract_template')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">الحد الأقصى 20 ميجابايت. استبدال الملف يحذف النسخة السابقة.</div>
                        </div>
                        @if ($project->hasContractTemplate())
                            <input type="hidden" name="remove_contract_template" value="0">
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="remove_contract_template" value="1" id="remove-contract-template" @checked(old('remove_contract_template') == '1')>
                                <label class="form-check-label text-danger-emphasis small" for="remove-contract-template">حذف قالب العقد المخزّن لهذا المشروع</label>
                            </div>
                        @endif

                        <button type="submit" class="btn btn-primary">حفظ</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
