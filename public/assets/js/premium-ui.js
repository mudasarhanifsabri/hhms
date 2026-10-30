(() => {
    'use strict';

    const ready = (callback) => document.readyState === 'loading'
        ? document.addEventListener('DOMContentLoaded', callback, { once: true })
        : callback();

    ready(() => {
        const shell = document.body;
        if (!shell.classList.contains('admin-pwa-shell')) return;

        // Derive useful placeholders from nearby labels without overriding intentional copy.
        document.querySelectorAll('input.form-control:not([type="date"]):not([type="file"]):not([type="checkbox"]):not([type="radio"]):not([type="hidden"]), textarea.form-control').forEach((field) => {
            if (field.placeholder || field.disabled || field.readOnly) return;
            const container = field.closest('.col, [class*="col-"], .mb-3, .form-group, .modal-body') || field.parentElement;
            const label = container?.querySelector(`label[for="${CSS.escape(field.id || '__none__')}"]`) || container?.querySelector('.form-label, label');
            const name = (label?.textContent || field.getAttribute('aria-label') || field.name || '').replace(/\s+/g, ' ').replace(/\*/g, '').trim();
            if (!name || name.length > 55 || /password/i.test(field.type)) return;
            field.placeholder = field.tagName === 'TEXTAREA' ? `Add ${name.toLowerCase()}…` : `Enter ${name.toLowerCase()}`;
        });

        // Make required fields obvious and accessible.
        document.querySelectorAll('input[required], select[required], textarea[required]').forEach((field) => {
            field.setAttribute('aria-required', 'true');
            const label = field.id ? document.querySelector(`label[for="${CSS.escape(field.id)}"]`) : field.closest('.col, [class*="col-"], .form-group, .mb-3')?.querySelector('label');
            if (label && !label.querySelector('.hh-required') && !/\*/.test(label.textContent)) {
                label.insertAdjacentHTML('beforeend', '<span class="hh-required text-danger" aria-hidden="true">*</span>');
            }
        });

        // Stop accidental duplicate submissions and clearly communicate progress.
        document.querySelectorAll('form').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (form.dataset.hhSubmitting === '1') {
                    event.preventDefault();
                    return;
                }
                if (event.defaultPrevented || !form.checkValidity()) return;
                form.dataset.hhSubmitting = '1';
                const button = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
                if (!(button instanceof HTMLButtonElement)) return;
                button.classList.add('hh-busy');
                button.setAttribute('aria-busy', 'true');
                if (!button.querySelector('.hh-submit-spinner')) button.insertAdjacentHTML('afterbegin', '<span class="hh-submit-spinner" aria-hidden="true"></span>');
                setTimeout(() => {
                    if (document.visibilityState === 'visible') {
                        form.dataset.hhSubmitting = '0';
                        button.classList.remove('hh-busy');
                        button.removeAttribute('aria-busy');
                    }
                }, 12000);
            });
        });

        // Improve modal keyboard flow and restore forms when a validation-free modal closes.
        document.querySelectorAll('.modal').forEach((modal) => {
            modal.addEventListener('shown.bs.modal', () => {
                const first = modal.querySelector('[autofocus], input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])');
                if (first && !/file/i.test(first.type || '')) first.focus({ preventScroll: true });
            });
        });

        // Tooltips turn icon-only controls into understandable actions.
        if (window.bootstrap?.Tooltip) {
            document.querySelectorAll('[title]:not([data-bs-toggle]):not(.form-control):not(.form-select)').forEach((element) => {
                element.setAttribute('data-bs-toggle', 'tooltip');
                new window.bootstrap.Tooltip(element, { container: 'body', trigger: 'hover focus' });
            });
        }

        // Images below the initial viewport should not delay first paint.
        document.querySelectorAll('img:not([loading])').forEach((image, index) => {
            if (index > 2 && !image.closest('.topbar, .main-nav, .modal.show')) image.loading = 'lazy';
            image.decoding = 'async';
        });

        // Standardize legacy empty table messages.
        document.querySelectorAll('tbody td[colspan]').forEach((cell) => {
            if (/\b(no|nothing|empty)\b/i.test(cell.textContent.trim())) cell.classList.add('hh-empty-state');
        });
    });
})();
