<?php

/**
 * Smoke test root URL.
 *
 * Sebelumnya menegaskan tamu di-redirect ke halaman login. Sejak landing page
 * publik ditambahkan, root justru melayani halaman tersebut untuk tamu —
 * perilaku redirect hanya berlaku bagi pengguna yang sudah masuk. Cakupan
 * lengkapnya ada di LandingPageTest.
 */
it('serves the public landing page at the root url for guests', function () {
    $this->get('/')
        ->assertOk()
        ->assertViewIs('landing');
});
