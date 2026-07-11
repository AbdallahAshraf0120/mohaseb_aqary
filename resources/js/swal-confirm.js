import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Forms with data-swal-confirm="…" show SweetAlert2 instead of blocking confirm().
 * Optional data-swal-ajax: submit via fetch and remove the closest table row (no full reload).
 * Programmatic form.submit() does not re-dispatch submit, so no loop.
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

        if (! isConfirmed) {
            return;
        }

        if (form.hasAttribute('data-swal-ajax')) {
            await submitFormAjax(form);
            return;
        }

        form.submit();
    }, true);
}

async function submitFormAjax(form) {
    const submitters = form.querySelectorAll('button[type="submit"], input[type="submit"]');
    submitters.forEach((el) => {
        el.disabled = true;
    });

    try {
        const methodInput = form.querySelector('input[name="_method"]');
        const method = (methodInput?.value || form.method || 'POST').toUpperCase();
        const body = new FormData(form);

        const response = await fetch(form.action, {
            method: method === 'GET' ? 'GET' : 'POST',
            headers: {
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: method === 'GET' ? undefined : body,
            credentials: 'same-origin',
        });

        let payload = null;
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            payload = await response.json();
        }

        if (! response.ok) {
            const message = payload?.message
                || (typeof payload?.error === 'string' ? payload.error : null)
                || 'تعذّر تنفيذ العملية.';
            throw new Error(message);
        }

        const row = form.closest('tr');
        const tbody = row?.parentElement;
        if (row) {
            row.remove();
        }

        if (tbody instanceof HTMLTableSectionElement && tbody.querySelectorAll('tr').length === 0) {
            const cols = tbody.closest('table')?.querySelectorAll('thead th').length || 1;
            const empty = document.createElement('tr');
            empty.innerHTML = `<td colspan="${cols}" class="text-center text-muted">لا توجد مصروفات حتى الآن.</td>`;
            tbody.appendChild(empty);
        }

        await Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: payload?.message || 'تم الحذف بنجاح.',
            showConfirmButton: false,
            timer: 2200,
            timerProgressBar: true,
            dir: 'rtl',
        });
    } catch (error) {
        await Swal.fire({
            icon: 'error',
            title: 'خطأ',
            text: error instanceof Error ? error.message : 'تعذّر تنفيذ العملية.',
            confirmButtonText: 'حسناً',
            dir: 'rtl',
        });
        submitters.forEach((el) => {
            el.disabled = false;
        });
    }
}
