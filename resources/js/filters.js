export function initFilterAutoSubmit() {
    document.querySelectorAll('[data-filter-form] [data-filter-auto]').forEach((field) => {
        field.addEventListener('change', () => {
            field.closest('form').submit();
        });
    });
}
