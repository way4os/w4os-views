// A list with the detail of the item it selects (W4OS\Views\ListDetailView): a row's link fetches the page of that item and swaps
// its detail in place (its teleport links set up), the selected row marked, the address kept in the history (Back shows the detail
// before); a link opened in a new tab, or a page the script cannot read, loads as it would without it.
(() => {
    const swap = async (box, url, push) => {
        const region = box.dataset.region;
        const current = document.getElementById(region);
        let next = null;
        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            next = response.ok ? new DOMParser().parseFromString(await response.text(), 'text/html').getElementById(region) : null;
        } catch (error) {}
        if (!current || !next) {
            window.location.href = url;
            return;
        }
        current.replaceWith(next);
        box.querySelector('.w4os-ld')?.classList.toggle('has-detail', !next.hidden);
        const selected = new URL(url, window.location.href).searchParams.get(box.dataset.param);
        box.querySelectorAll('a[data-w4os-select]').forEach((link) => {
            const on = link.dataset.w4osSelect === selected;
            on ? link.setAttribute('aria-current', 'true') : link.removeAttribute('aria-current');
            link.closest('tr')?.classList.toggle('is-selected', on);
        });
        window.W4OSTeleportLinks?.setup(next);
        push && history.pushState({ w4osListDetail: true }, '', url);
        next.querySelector('.w4os-detail-title')?.focus();
    };
    document.addEventListener('click', (event) => {
        const link = event.target.closest?.('a[data-w4os-select]');
        const box = link?.closest('[data-w4os-list-detail]');
        if (!box || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey || event.button !== 0) {
            return;
        }
        event.preventDefault();
        swap(box, link.href, true);
    });
    window.addEventListener('popstate', () => {
        document.querySelectorAll('[data-w4os-list-detail]').forEach((box) => swap(box, window.location.href, false));
    });
})();
