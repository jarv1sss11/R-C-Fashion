export function initFulfillmentAutoSubmit() {
    document.querySelectorAll('[data-fulfillment-auto]').forEach((field) => {
        field.addEventListener('change', () => {
            field.closest('form').submit();
        });
    });
}
