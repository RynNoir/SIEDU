<?php

use App\Models\User;

test('tamu melihat landing page di root', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Sistem Evaluasi Dosen Terpadu')
        ->assertSee('Cara Kerja')
        ->assertSee('Anonimitas');
});

test('landing page menautkan ke halaman login', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(route('login'), escape: false);
});

test('ambang minimum responden pada landing page mengikuti konfigurasi', function () {
    config(['evaluation.anonymity_min_respondents' => 7]);

    $this->get('/')
        ->assertOk()
        ->assertSee('7+ responden');
});

test('pengguna yang sudah masuk dilempar ke dashboard sesuai perannya', function (string $role, string $dashboardRoute) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)->get('/')->assertRedirect(route($dashboardRoute));
})->with([
    'admin' => ['admin', 'admin.dashboard'],
    'dosen' => ['lecturer', 'lecturer.dashboard'],
    'mahasiswa' => ['student', 'student.dashboard'],
    'kaprodi' => ['kaprodi', 'kaprodi.dashboard'],
]);
