export function initNavbarToggle() {
    const toggle = document.getElementById('navToggle');
    const menu = document.getElementById('navMenu');

    if (!toggle || !menu) {
        return;
    }

    toggle.addEventListener('click', () => {
        const isOpen = menu.classList.toggle('is-open');
        toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
}
