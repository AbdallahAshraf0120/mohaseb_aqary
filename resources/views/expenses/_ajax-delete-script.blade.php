<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script>
(function () {
    if (window.__expenseAjaxDeleteBound) return;
    window.__expenseAjaxDeleteBound = true;

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
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-expense-ajax-delete')) {
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
                    row.remove();
                    var tbody = document.querySelector('.card-body table tbody');
                    if (tbody && !tbody.querySelector('tr')) {
                        tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">لا توجد مصروفات حتى الآن.</td></tr>';
                    }
                    Swal.fire({
                        icon: 'success',
                        title: 'تم',
                        text: (data && data.message) || 'تم حذف المصروف بنجاح.',
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
