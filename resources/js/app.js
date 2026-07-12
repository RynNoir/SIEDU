import Alpine from 'alpinejs';

window.Alpine = Alpine;

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
