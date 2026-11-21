// Teleport links (W4OS\Views\TeleportLink): the links of the client the page is seen from (the viewer's own browser, by its user
// agent), the menu of the other ones opened by its chevron, kept within the window, closed on Escape or a click outside, its items
// reached with the arrows; the address copied, or shown selected where the browser gives no clipboard.
(() => {
    document.querySelectorAll('[data-w4os-tp]').forEach((tp) => {
        let client = tp.dataset.client;
        try {
            client = tp.dataset.viewerPattern && new RegExp(tp.dataset.viewerPattern, 'i').test(navigator.userAgent) ? 'viewer' : 'browser';
        } catch (error) {}
        tp.dataset.client = client;
        // Its main link, the one of that client: none where it has none (the text alone)
        const main = tp.querySelector('a.w4os-tp-main');
        if (main) {
            main.dataset[client] ? main.setAttribute('href', main.dataset[client]) : main.removeAttribute('href');
        }
    });
    const menuOf = (tp) => tp.querySelector('.w4os-tp-menu');
    const items = (tp) => [...tp.querySelectorAll('.w4os-tp-item, .w4os-tp-address')].filter((item) => item.offsetParent !== null);
    const close = (tp) => {
        const toggle = tp.querySelector('.w4os-tp-toggle');
        if (toggle && !menuOf(tp).hidden) {
            menuOf(tp).hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }
    };
    const select = (copy, text) => {
        const input = document.createElement('input');
        input.type = 'text';
        input.readOnly = true;
        input.value = text;
        input.className = 'w4os-tp-address';
        input.setAttribute('aria-label', copy.textContent);
        copy.replaceWith(input);
        input.focus();
        input.select();
    };
    document.addEventListener('click', (event) => {
        document.querySelectorAll('[data-w4os-tp]').forEach((tp) => tp.contains(event.target) || close(tp));
        const toggle = event.target.closest('.w4os-tp-toggle');
        if (toggle) {
            const tp = toggle.closest('[data-w4os-tp]');
            const open = menuOf(tp).hidden;
            const menu = menuOf(tp);
            menu.hidden = !open;
            toggle.setAttribute('aria-expanded', String(open));
            if (open && !tp.classList.contains('w4os-tp-card')) {
                menu.classList.remove('w4os-tp-menu-end');
                menu.getBoundingClientRect().right > document.documentElement.clientWidth && menu.classList.add('w4os-tp-menu-end');
            }
            open && items(tp)[0]?.focus();
            return;
        }
        const copy = event.target.closest('.w4os-tp-copy');
        if (copy) {
            const text = copy.dataset.w4osTpCopy;
            const label = copy.textContent;
            const done = () => {
                copy.textContent = copy.dataset.copied || label;
                setTimeout(() => { copy.textContent = label; }, 2000);
            };
            navigator.clipboard ? navigator.clipboard.writeText(text).then(done, () => select(copy, text)) : select(copy, text);
        }
    });
    document.addEventListener('keydown', (event) => {
        const tp = event.target.closest?.('[data-w4os-tp]');
        if (!tp || !tp.querySelector('.w4os-tp-toggle') || menuOf(tp).hidden) {
            return;
        }
        if (event.key === 'Escape') {
            close(tp);
            tp.querySelector('.w4os-tp-toggle').focus();
            event.preventDefault();
        } else if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            const list = items(tp);
            const at = list.indexOf(document.activeElement);
            list[(at + (event.key === 'ArrowDown' ? 1 : -1) + list.length) % list.length]?.focus();
            event.preventDefault();
        }
    });
})();
