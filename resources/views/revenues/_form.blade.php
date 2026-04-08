@csrf

@php
    $selectedContractId = (string) old('contract_id', $revenue->contract_id ?? '');
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">العقد</label>
        <select name="contract_id" id="contract_id" class="form-select" required>
            <option value="">اختر العقد</option>
            @foreach ($contracts as $contract)
                <option value="{{ $contract->id }}"
                        data-client-id="{{ $contract->client_id }}"
                        data-client-name="{{ $contract->client?->name }}"
                        data-sale-id="{{ $contract->sale_id }}"
                        data-remaining="{{ $contract->remaining_amount }}"
                        @selected($selectedContractId === (string) $contract->id)>
                    CT-{{ now()->format('Y') }}-{{ str_pad((string) $contract->id, 3, '0', STR_PAD_LEFT) }} |
                    {{ $contract->client?->name }} | متبقي: {{ number_format((float) $contract->remaining_amount, 2) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">العميل</label>
        <input type="text" id="client_name_display" class="form-control" readonly>
        <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id', $revenue->client_id ?? '') }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">مرجع البيعة</label>
        <input type="text" id="sale_display" class="form-control" readonly>
        <input type="hidden" name="sale_id" id="sale_id" value="{{ old('sale_id', $revenue->sale_id ?? '') }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">قيمة التحصيل</label>
        <input type="number" step="0.01" min="1" name="amount" class="form-control" value="{{ old('amount', $revenue->amount ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">نوع الإيراد</label>
        <select name="category" class="form-select" required>
            @foreach (['قسط بيع', 'مقدم تعاقد', 'رسوم خدمات'] as $category)
                <option value="{{ $category }}" @selected(old('category', $revenue->category ?? 'قسط بيع') === $category)>{{ $category }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">طريقة الدفع</label>
        <select name="payment_method" class="form-select" required>
            @foreach (['cash' => 'نقدي', 'bank_transfer' => 'تحويل بنكي', 'check' => 'شيك'] as $value => $label)
                <option value="{{ $value }}" @selected(old('payment_method', $revenue->payment_method ?? 'cash') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">تاريخ التحصيل</label>
        <input type="date" name="paid_at" class="form-control"
               value="{{ old('paid_at', isset($revenue->paid_at) ? $revenue->paid_at->format('Y-m-d') : now()->format('Y-m-d')) }}" required>
    </div>

    <div class="col-md-6">
        <label class="form-label">المصدر</label>
        <input type="text" name="source" class="form-control" value="{{ old('source', $revenue->source ?? '') }}" placeholder="مثال: قسط شهري">
    </div>
    <div class="col-md-6">
        <label class="form-label">ملاحظات</label>
        <input type="text" name="notes" class="form-control" value="{{ old('notes', $revenue->notes ?? '') }}">
    </div>
</div>

@if ($errors->any())
    <div class="alert alert-danger mt-3 mb-0">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<script>
    (function () {
        const contractSelect = document.getElementById('contract_id');
        const clientDisplay = document.getElementById('client_name_display');
        const saleDisplay = document.getElementById('sale_display');
        const clientIdInput = document.getElementById('client_id');
        const saleIdInput = document.getElementById('sale_id');

        function syncContractMeta() {
            const selected = contractSelect.options[contractSelect.selectedIndex];
            if (!selected || !selected.value) {
                clientDisplay.value = '';
                saleDisplay.value = '';
                clientIdInput.value = '';
                saleIdInput.value = '';
                return;
            }

            clientDisplay.value = selected.dataset.clientName || '';
            saleDisplay.value = selected.dataset.saleId ? 'SL-' + String(selected.dataset.saleId).padStart(3, '0') : '-';
            clientIdInput.value = selected.dataset.clientId || '';
            saleIdInput.value = selected.dataset.saleId || '';
        }

        contractSelect.addEventListener('change', syncContractMeta);
        syncContractMeta();
    })();
</script>
