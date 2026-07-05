export function initBulkProductActions() {
    const form = document.querySelector('[data-bulk-form]');

    if (! form) {
        return;
    }

    const selectAll = document.querySelector('[data-bulk-select-all]');
    const checkboxes = () => Array.from(document.querySelectorAll('[data-bulk-checkbox]'));

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes().forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
        });
    }

    form.addEventListener('submit', (event) => {
        const selected = checkboxes().filter((checkbox) => checkbox.checked);

        if (selected.length === 0) {
            event.preventDefault();
            window.alert('Select at least one product first.');

            return;
        }

        form.querySelectorAll('input[name="product_ids[]"]').forEach((input) => input.remove());

        selected.forEach((checkbox) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = checkbox.value;
            form.appendChild(input);
        });
    });
}
