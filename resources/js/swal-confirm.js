import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Forms with data-swal-confirm="…" show SweetAlert2 instead of blocking confirm().
 * Optional data-swal-ajax: submit via fetch (no full page reload).
 * Optional data-swal-remove: CSS selector for element to remove on success (default: closest tr).
 */
export function registerSwalConfirmForms() {
    document.addEventListener('submit', async (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
            return;
        }
        const msg = form.getAttribute('data-swal-confirm');
        if (msg === null || msg === '') {
            return;
        }
        event.preventDefault();
        event.stopPropagation();

        if (form.dataset.swalBusy === '1') {
            return;
        }

        const { isConfirmed } = await Swal.fire({
            title: 'تأكيد',
            text: msg,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            reverseButtons: true,
            focusCancel: true,
            dir: 'rtl',
        });

        if (!isConfirmed) {
            return;
        }

        if (!form.hasAttribute('data-swal-ajax')) {
            form.submit();

            return;
        }

        form.dataset.swalBusy = '1';
        const submitBtn = form.querySelector('[type="submit"]');
        if (submitBtn instanceof HTMLButtonElement) {
            submitBtn.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: form.method || 'POST',
                body: new FormData(form),
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
            });

            let payload = null;
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                payload = await response.json();
            }

            if (!response.ok) {
                throw new Error(payload?.message || 'تعذّر تنفيذ الحذف.');
            }

            const redirectUrl = payload?.redirect;
            const removeSelector = form.getAttribute('data-swal-remove');
            const row = removeSelector
                ? document.querySelector(removeSelector)
                : form.closest('tr');

            if (row) {
                row.remove();
                const tbody = document.querySelector('table tbody');
                if (tbody && tbody.querySelectorAll('tr').length === 0) {
                    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">لا توجد مصروفات حتى الآن.</td></tr>';
                }
            } else if (redirectUrl) {
                window.location.href = redirectUrl;

                return;
            }

            await Swal.fire({
                icon: 'success',
                title: 'تم',
                text: payload?.message || 'تم الحذف بنجاح.',
                timer: 1600,
                showConfirmButton: false,
                dir: 'rtl',
            });
        } catch (error) {
            await Swal.fire({
                icon: 'error',
                title: 'خطأ',
                text: error instanceof Error ? error.message : 'تعذّر تنفيذ الحذف.',
                confirmButtonText: 'حسناً',
                dir: 'rtl',
            });
            if (submitBtn instanceof HTMLButtonElement) {
                submitBtn.disabled = false;
            }
            form.dataset.swalBusy = '0';
        }
    }, true);
}
