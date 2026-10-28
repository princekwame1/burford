import Swal from 'sweetalert2';

// One SweetAlert2 look for the whole app: our own buttons, rounded cards, dark-mode aware (see app.css).
const Dialog = Swal.mixin({
    buttonsStyling: false,
    reverseButtons: true,
    focusCancel: false,
    customClass: {
        popup: 'swal-app',
        title: 'swal-app-title',
        htmlContainer: 'swal-app-text',
        actions: 'swal-app-actions',
        confirmButton: 'btn btn-primary',
        cancelButton: 'btn btn-secondary',
    },
});

const Toast = Swal.mixin({
    toast: true,
    position: 'bottom-end',
    showConfirmButton: false,
    showCloseButton: true,
    timer: 5000,
    timerProgressBar: true,
    customClass: { popup: 'swal-app swal-app-toast', title: 'swal-app-toast-title' },
    didOpen: (el) => {
        el.addEventListener('mouseenter', Swal.stopTimer);
        el.addEventListener('mouseleave', Swal.resumeTimer);
    },
});

window.Swal = Swal;

window.toast = (icon, title) => Toast.fire({ icon, title });

window.alertBox = (icon, title, text) => Dialog.fire({ icon, title, text, confirmButtonText: 'OK' });

/** Resolves true when the user confirms. tone: 'danger' (destructive) or 'primary'. */
window.confirmAction = async ({ title = 'Are you sure?', text = '', confirmText = 'Yes, continue', tone = 'primary' } = {}) => {
    const danger = tone === 'danger';
    const result = await Dialog.fire({
        icon: danger ? 'warning' : 'question',
        title,
        text,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: 'Cancel',
        customClass: { confirmButton: danger ? 'btn btn-danger' : 'btn btn-primary' },
    });
    return result.isConfirmed;
};

/*
 * Declarative confirmations. Put data-confirm="Message" on a <form> or on its submit button
 * (the button wins), plus optional data-confirm-title, data-confirm-button and data-confirm-tone="danger".
 * Alpine can bind it (:data-confirm="…") so the text reflects live form values; a null binding skips the prompt.
 */
document.addEventListener('submit', async (e) => {
    const form = e.target;
    const source = e.submitter?.hasAttribute('data-confirm') ? e.submitter : form;
    const text = source.getAttribute('data-confirm');
    if (!text || form.dataset.confirmed) return;

    e.preventDefault();
    const ok = await window.confirmAction({
        text,
        title: source.dataset.confirmTitle,
        confirmText: source.dataset.confirmButton,
        tone: source.dataset.confirmTone,
    });
    if (!ok) return;

    form.dataset.confirmed = '1';
    form.requestSubmit(e.submitter ?? null);
    delete form.dataset.confirmed;
});

// Flash messages rendered by the layout into window.__flash.
document.addEventListener('DOMContentLoaded', () => {
    const flash = window.__flash || {};
    if (flash.error) {
        // Errors stay on screen until read.
        window.alertBox('error', flash.errorTitle || 'Something went wrong', flash.error);
    } else if (flash.success) {
        window.toast('success', flash.success);
    } else if (flash.validation) {
        window.toast('error', flash.validation);
    }
});
