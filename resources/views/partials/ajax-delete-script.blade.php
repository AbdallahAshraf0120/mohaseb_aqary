{{-- Shared AJAX delete with SweetAlert2 (CDN) — no Vite rebuild needed --}}
@php
    $ajaxDeleteClass = $ajaxDeleteClass ?? 'js-ajax-delete';
    $ajaxEmptyColspan = $ajaxEmptyColspan ?? 8;
    $ajaxEmptyMessage = $ajaxEmptyMessage ?? 'لا توجد سجلات حتى الآن.';
@endphp
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
(function () {
    var bindKey = '__ajaxDeleteBound_' + @json($ajaxDeleteClass);
    if (window[bindKey]) return;
    window[bindKey] = true;

    var formClass = @json($ajaxDeleteClass);
    var emptyColspan = @json($ajaxEmptyColspan);
    var emptyMessage = @json($ajaxEmptyMessage);

    function swalConfirm(msg) {
        return Swal.fire({
            title: 'تأكيد',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true,
            dir: 'rtl'
        }).then(function (result) {
            return !!result.isConfirmed;
        });
    }

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains(formClass)) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        if (form.dataset.busy === '1') return;

        var msg = form.getAttribute('data-confirm') || 'تأكيد الحذف؟';

        swalConfirm(msg).then(function (ok) {
            if (!ok) return;

            form.dataset.busy = '1';
            var btn = form.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(function (res) {
                return res.json().then(function (data) {
                    if (!res.ok) throw new Error((data && data.message) || 'تعذّر تنفيذ الحذف.');
                    return data;
                }, function () {
                    throw new Error('تعذّر تنفيذ الحذف.');
                });
            })
            .then(function (data) {
                var row = form.closest('tr');
                if (row) {
                    var table = row.closest('table');
                    row.remove();
                    var tbody = table ? table.querySelector('tbody') : null;
                    if (tbody && !tbody.querySelector('tr')) {
                        tbody.innerHTML = '<tr><td colspan="' + emptyColspan + '" class="text-center text-muted">' + emptyMessage + '</td></tr>';
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'تم',
                        text: (data && data.message) || 'تم الحذف بنجاح.',
                        timer: 1400,
                        showConfirmButton: false,
                        dir: 'rtl'
                    });
                    return;
                }
                if (data && data.redirect) {
                    window.location.href = data.redirect;
                }
            })
            .catch(function (err) {
                Swal.fire({
                    icon: 'error',
                    title: 'خطأ',
                    text: err.message || 'تعذّر تنفيذ الحذف.',
                    confirmButtonText: 'حسناً',
                    dir: 'rtl'
                });
                form.dataset.busy = '0';
                if (btn) btn.disabled = false;
            });
        });
    }, true);
})();
</script>
