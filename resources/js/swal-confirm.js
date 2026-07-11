import Swal from 'sweetalert2';
import 'sweetalert2/dist/sweetalert2.min.css';

/**
 * Forms with data-swal-confirm="…" show SweetAlert2 instead of blocking confirm().
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

        if (isConfirmed) {
            form.submit();
        }
    }, true);
}
