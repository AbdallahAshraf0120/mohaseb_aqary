@csrf

@php
    $selectedContractId = (string) old('contract_id', $revenue->contract_id ?? '');
    $contractSuggestedAmounts = $contractSuggestedAmounts ?? [];
    $skipInitialAmountSuggestion = filled(old('amount')) || (isset($revenue) && $revenue->exists);
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">العقد</label>
        <select name="contract_id" id="contract_id" class="form-select" required>
            <option value="">اختر العقد</option>
            @foreach ($contracts as $contract)
                @php
                    $suggested = $contractSuggestedAmounts[$contract->id] ?? null;
                @endphp
                <option value="{{ $contract->id }}"
                        data-client-id="{{ $contract->client_id }}"
                        data-client-name="{{ $contract->client?->name }}"
                        data-sale-id="{{ $contract->sale_id }}"
                        data-remaining="{{ $contract->remaining_amount }}"
                        data-suggested-amount="{{ $suggested !== null ? number_format((float) $suggested, 2, '.', '') : '' }}"
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
        <input type="number" step="0.01" min="0.01" name="amount" id="revenue_amount" class="form-control"
               value="{{ old('amount', $revenue->amount ?? '') }}" required>
        <small class="text-muted">يُقترح تلقائياً من <strong>القسط القادم</strong> (جدول البيعة) عند اختيار العقد؛ يمكنك التعديل إن لزم.</small>
        <div class="mt-1">
            <button type="button" class="btn btn-outline-secondary btn-sm" id="apply-suggested-amount">تطبيق مبلغ القسط القادم</button>
        </div>
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
        const amountInput = document.getElementById('revenue_amount');
        const skipInitialAmountSuggestion = @json($skipInitialAmountSuggestion);

        function applySuggestedAmountFromContract() {
            if (!amountInput || !contractSelect) {
                return;
            }
            const selected = contractSelect.options[contractSelect.selectedIndex];
            if (!selected || !selected.value) {
                return;
            }
            const suggested = selected.dataset.suggestedAmount;
            if (suggested !== undefined && suggested !== '') {
                amountInput.value = suggested;
            }
        }

        function syncContractMeta(options) {
            const fillAmount = options && options.fillAmount;
            const selected = contractSelect?.options?.[contractSelect.selectedIndex];
            if (!selected || !selected.value) {
                if (clientDisplay) clientDisplay.value = '';
                if (saleDisplay) saleDisplay.value = '';
                if (clientIdInput) clientIdInput.value = '';
                if (saleIdInput) saleIdInput.value = '';
                return;
            }

            if (clientDisplay) clientDisplay.value = selected.dataset.clientName || '';
            if (saleDisplay) saleDisplay.value = selected.dataset.saleId ? 'SL-' + String(selected.dataset.saleId).padStart(3, '0') : '-';
            if (clientIdInput) clientIdInput.value = selected.dataset.clientId || '';
            if (saleIdInput) saleIdInput.value = selected.dataset.saleId || '';

            if (fillAmount) {
                applySuggestedAmountFromContract();
            }
        }

        contractSelect.addEventListener('change', function () {
            syncContractMeta({ fillAmount: true });
        });

        document.getElementById('apply-suggested-amount')?.addEventListener('click', function () {
            applySuggestedAmountFromContract();
        });

        syncContractMeta({ fillAmount: !skipInitialAmountSuggestion });
    })();
</script>
