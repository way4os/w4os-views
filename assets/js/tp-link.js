// Teleport links (W4OS\Views\TeleportLink): the links of the client the page is seen from (the viewer's own browser, by its user
// agent), the menu of all of them opened by its chevron, kept within the window, closed on Escape or a click outside, its items
// reached with the arrows; the address copied in one click (its button saying so for a while), by the clipboard of the browser,
// else the copy of a selected text (a page of http), or shown selected only when neither copies.
(() => {
    // The links of a part of the page: all of them at its load, the ones a page adds later when it calls it (W4OSTeleportLinks.setup)
    const setup = (root = document) => root.querySelectorAll('[data-w4os-tp]').forEach((tp) => {
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
    setup();
    window.W4OSTeleportLinks = { setup };
    const menuOf = (tp) => tp.querySelector('.w4os-tp-menu');
    const items = (tp) => [...tp.querySelectorAll('.w4os-tp-item, .w4os-tp-address')].filter((item) => item.offsetParent !== null);
    const close = (tp) => {
        const toggle = tp.querySelector('.w4os-tp-toggle');
        if (toggle && !menuOf(tp).hidden) {
            menuOf(tp).hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
        }
    };
    // The copy of a text selected out of sight: what a page of http has (navigator.clipboard is for a secure context only)
    const command = (text) => {
        const area = document.createElement('textarea');
        area.value = text;
        area.setAttribute('readonly', '');
        area.style.position = 'fixed';
        area.style.top = '-1000px';
        area.style.opacity = '0';
        document.body.appendChild(area);
        area.select();
        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (error) {}
        area.remove();
        return copied;
    };
    const select = (copy, text) => {
        const input = document.createElement('input');
        input.type = 'text';
        input.readOnly = true;
        input.value = text;
        input.className = 'w4os-tp-address';
        input.setAttribute('aria-label', copy.getAttribute('aria-label') || copy.textContent);
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
            if (open) {
                menu.classList.remove('w4os-tp-menu-end');
                menu.getBoundingClientRect().right > document.documentElement.clientWidth && menu.classList.add('w4os-tp-menu-end');
            }
            open && items(tp)[0]?.focus();
            return;
        }
        const copy = event.target.closest('.w4os-tp-copy');
        if (copy) {
            const text = copy.dataset.w4osTpCopy;
            // Said on its label, or on its button when it is an icon alone
            const label = copy.querySelector('.w4os-tp-label');
            const said = label ? label.textContent : copy.getAttribute('aria-label');
            const say = (words) => label ? (label.textContent = words) : (copy.setAttribute('aria-label', words), copy.title = words);
            const done = () => {
                copy.classList.add('is-copied');
                say(copy.dataset.copied || said);
                setTimeout(() => { copy.classList.remove('is-copied'); say(said); }, 2000);
            };
            // The clipboard of the browser, else the copy of a selected text; the address shown selected only when nothing could copy
            const fallback = () => {
                const copied = command(text);
                copied ? done() : select(copy, text);
            };
            navigator.clipboard && window.isSecureContext ? navigator.clipboard.writeText(text).then(done).catch(fallback) : fallback();
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
