export function initNewsletterForm() {
    document.querySelectorAll('[data-newsletter-form]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            event.preventDefault();

            const note = form.parentElement.querySelector('[data-newsletter-note]');

            form.hidden = true;

            if (note) {
                note.hidden = false;
            }
        });
    });
}
