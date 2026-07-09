/**
 * Autocomplete for the search-bar component.
 *
 * Attaches to every .search-bar-wrapper that has a data-suggestions-url
 * attribute. Fetches suggestions from that URL with a 300 ms debounce,
 * renders them in the sibling .search-suggestions panel, and supports
 * keyboard navigation (↑ ↓ Escape) and click-to-navigate.
 */
export function initSearchAutocomplete() {
    document.querySelectorAll('.search-bar-wrapper').forEach(wrapper => {
        const input    = wrapper.querySelector('.search-bar-input');
        const dropdown = wrapper.querySelector('.search-suggestions');
        const url      = (wrapper.dataset.suggestionsUrl || '').trim();

        if (!input || !dropdown || !url) return;

        let timer       = null;
        let activeIndex = -1;

        // ─── Helpers ──────────────────────────────────────────────────────────

        function escapeHtml(str) {
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        }

        function getItems() {
            return dropdown.querySelectorAll('.suggestion-item');
        }

        function setActive(index) {
            const items = getItems();
            activeIndex = Math.max(-1, Math.min(index, items.length - 1));
            items.forEach((el, i) => {
                el.classList.toggle('is-active', i === activeIndex);
                el.setAttribute('aria-selected', i === activeIndex ? 'true' : 'false');
            });
        }

        // ─── Render ───────────────────────────────────────────────────────────

        function render(suggestions) {
            dropdown.innerHTML = '';
            activeIndex = -1;

            if (!suggestions.length) {
                dropdown.hidden = true;
                return;
            }

            suggestions.forEach((item, i) => {
                const btn = document.createElement('button');
                btn.type      = 'button';
                btn.className = 'suggestion-item';
                btn.setAttribute('role', 'option');
                btn.setAttribute('aria-selected', 'false');
                btn.dataset.index = i;

                btn.innerHTML = `
                    <span class="suggestion-text">${escapeHtml(item.text)}</span>
                    <span class="suggestion-type">${escapeHtml(item.type)}</span>
                `;

                // mousedown fires before input blur, so the dropdown is still
                // visible when the click registers.
                btn.addEventListener('mousedown', e => {
                    e.preventDefault();
                    if (item.url) {
                        window.location.href = item.url;
                    } else {
                        input.value = item.text;
                        hide();
                        input.closest('form').submit();
                    }
                });

                dropdown.appendChild(btn);
            });

            dropdown.hidden = false;
        }

        // ─── Hide ─────────────────────────────────────────────────────────────

        function hide() {
            dropdown.hidden = true;
            activeIndex = -1;
        }

        // ─── Fetch ────────────────────────────────────────────────────────────

        function fetch_suggestions(term) {
            const params = new URLSearchParams({ q: term });
            fetch(`${url}?${params}`, {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            })
                .then(r => (r.ok ? r.json() : []))
                .then(data => render(Array.isArray(data) ? data : []))
                .catch(() => hide());
        }

        // ─── Events ───────────────────────────────────────────────────────────

        input.addEventListener('input', () => {
            clearTimeout(timer);
            const term = input.value.trim();
            if (term.length < 2) { hide(); return; }
            timer = setTimeout(() => fetch_suggestions(term), 300);
        });

        input.addEventListener('keydown', e => {
            const items = getItems();
            if (!items.length || dropdown.hidden) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                setActive(activeIndex + 1);
                if (activeIndex >= 0) {
                    input.value = items[activeIndex].querySelector('.suggestion-text').textContent;
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                setActive(activeIndex - 1);
                if (activeIndex >= 0) {
                    input.value = items[activeIndex].querySelector('.suggestion-text').textContent;
                }
            } else if (e.key === 'Escape') {
                hide();
            }
        });

        // Re-open on focus if the input already has a usable term.
        input.addEventListener('focus', () => {
            const term = input.value.trim();
            if (term.length >= 2) fetch_suggestions(term);
        });

        // Delay hiding so a click on a suggestion fires first.
        input.addEventListener('blur', () => setTimeout(hide, 180));
    });
}

/**
 * Expand/collapse behaviour for the compact navbar search icon
 * (search-bar.blade.php rendered with :compact="true"). Independent of
 * initSearchAutocomplete() above — that function attaches to every
 * .search-bar-wrapper regardless of compact state, so autocomplete keeps
 * working unmodified once the input is revealed.
 */
export function initCompactSearchToggle() {
    document.querySelectorAll('.search-bar-wrapper--compact').forEach(wrapper => {
        const toggle = wrapper.querySelector('.navbar-search-toggle');
        const input  = wrapper.querySelector('.search-bar-input');

        if (!toggle || !input) return;

        toggle.addEventListener('click', () => {
            const expanded = wrapper.classList.toggle('is-expanded');
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            if (expanded) input.focus();
        });

        // Collapse back to icon-only once focus leaves the whole wrapper,
        // but only if the input is empty — an in-progress query is left
        // open rather than silently discarded.
        wrapper.addEventListener('focusout', () => {
            setTimeout(() => {
                if (wrapper.contains(document.activeElement)) return;
                if (input.value.trim() !== '') return;
                wrapper.classList.remove('is-expanded');
                toggle.setAttribute('aria-expanded', 'false');
            }, 150);
        });
    });
}
