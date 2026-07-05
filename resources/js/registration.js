export function initRegistration() {
    const root = document.querySelector('[data-registration]');

    if (!root) {
        return;
    }

    const cards = root.querySelectorAll('[data-role-card]');
    const panels = root.querySelectorAll('[data-role-panel]');

    function openRole(role, { focusFirstField }) {
        cards.forEach((card) => {
            card.classList.toggle('is-active', card.dataset.roleCard === role);
        });

        panels.forEach((panel) => {
            const isMatch = panel.dataset.rolePanel === role;
            panel.classList.toggle('is-open', isMatch);

            if (isMatch && focusFirstField) {
                const firstField = panel.querySelector('input:not([type="hidden"])');
                if (firstField) {
                    window.requestAnimationFrame(() => firstField.focus());
                }
            }
        });
    }

    cards.forEach((card) => {
        card.addEventListener('click', () => openRole(card.dataset.roleCard, { focusFirstField: true }));
    });

    const initialRole = root.dataset.initialRole;
    if (initialRole) {
        openRole(initialRole, { focusFirstField: false });
    }
}
