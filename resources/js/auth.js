export function initPasswordToggle() {
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const input = button.closest('.input-field-control').querySelector('input');
            const willShow = input.type === 'password';

            input.type = willShow ? 'text' : 'password';
            button.classList.toggle('is-active', willShow);
            button.setAttribute('aria-label', willShow ? 'Hide password' : 'Show password');
        });
    });
}
