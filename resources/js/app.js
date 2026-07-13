import Alpine from 'alpinejs';
import htmx from 'htmx.org';

window.Alpine = Alpine;
window.htmx = htmx;

Alpine.start();

/**
 * Progressive enhancement: beri feedback "sedang mengirim" pada submit form mutasi
 * (checklist #13/#22). Berjalan di fase bubble agar menghormati onsubmit confirm()
 * yang membatalkan submit. Form GET (filter) & yang menandai data-no-loading dilewati.
 * Navigasi penuh me-reset tombol otomatis.
 */
document.addEventListener('submit', (event) => {
    if (event.defaultPrevented) {
        return;
    }

    const form = event.target;
    if (!(form instanceof HTMLFormElement)) {
        return;
    }
    if ((form.method || '').toLowerCase() === 'get' || form.hasAttribute('data-no-loading')) {
        return;
    }

    const button = event.submitter || form.querySelector('button[type="submit"], input[type="submit"]');
    if (!button || button.dataset.loading === '1') {
        return;
    }

    button.dataset.loading = '1';
    button.setAttribute('aria-busy', 'true');

    if (button.tagName === 'BUTTON' && !button.querySelector('.js-spinner')) {
        const spinner = document.createElement('span');
        spinner.className = 'js-spinner';
        spinner.setAttribute('aria-hidden', 'true');
        button.prepend(spinner);
    }

    // Nonaktifkan setelah submit terkirim agar value tombol tetap ikut ke server.
    window.requestAnimationFrame(() => {
        button.disabled = true;
    });
});

/**
 * Jaring pengaman htmx boost: shell (app-shell/student-layout) dibungkus hx-boost
 * dengan hx-select="#app-content" supaya sidebar/bottom-nav tak ikut tertukar saat
 * navigasi. Tapi sebagian rute bisa mengarah ke halaman BERBEDA struktur (redirect
 * paksa ganti password, halaman auth lain) yang tak punya #app-content sama sekali --
 * kalau itu terjadi, jangan swap parsial (akan kosong/rusak), pindah halaman penuh saja.
 */
document.body.addEventListener('htmx:beforeSwap', (event) => {
    const xhr = event.detail.xhr;
    if (xhr && !xhr.responseText.includes('id="app-content"')) {
        event.detail.shouldSwap = false;
        event.detail.isError = false;
        window.location.href = xhr.responseURL || window.location.href;
    }
});

/**
 * Sinkronisasi status "aktif" sidebar & bottom-nav setelah navigasi boosted.
 * Nav persisten (sidebar/bottom-nav) sengaja di luar #app-content supaya tak ikut
 * ditukar/flicker -- tapi berarti status aktifnya (dihitung server-side saat render
 * awal) jadi basi setelah pindah halaman. Sebelumnya ditangani via hx-swap-oob, tapi
 * itu meng-outerHTML-replace <nav> yang berisi link yang SEDANG diklik (ancestor dari
 * elemen pemicu event htmx) -- pitfall yang menyebabkan DOM ganda/sidebar tampak dobel.
 * Solusi ini murni toggle class lewat perbandingan location.pathname, tanpa mengganti
 * elemen DOM sama sekali.
 */
function syncNavActiveState() {
    document.querySelectorAll('[data-nav-link]').forEach((link) => {
        const base = link.dataset.navMatch;
        if (!base) {
            return;
        }

        const active = window.location.pathname === base || window.location.pathname.startsWith(base + '/');
        const activeClasses = (link.dataset.navActive || '').split(/\s+/).filter(Boolean);
        const inactiveClasses = (link.dataset.navInactive || '').split(/\s+/).filter(Boolean);

        link.classList.remove(...activeClasses, ...inactiveClasses);
        link.classList.add(...(active ? activeClasses : inactiveClasses));

        const icon = link.querySelector('[data-nav-icon]');
        if (icon) {
            const iconActiveClasses = (icon.dataset.navIconActive || '').split(/\s+/).filter(Boolean);
            const iconInactiveClasses = (icon.dataset.navIconInactive || '').split(/\s+/).filter(Boolean);

            icon.classList.remove(...iconActiveClasses, ...iconInactiveClasses);
            icon.classList.add(...(active ? iconActiveClasses : iconInactiveClasses));
        }
    });
}

document.addEventListener('DOMContentLoaded', syncNavActiveState);
document.body.addEventListener('htmx:afterSettle', syncNavActiveState);
document.body.addEventListener('htmx:pushedIntoHistory', syncNavActiveState);
window.addEventListener('popstate', syncNavActiveState);
