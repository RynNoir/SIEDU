<?php

use App\Models\User;

test('login mengarahkan tiap role ke dashboard-nya', function (string $role, string $route) {
    $user = User::factory()->{$role}()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route($route, absolute: false));
})->with([
    'admin' => ['admin', 'admin.dashboard'],
    'lecturer' => ['lecturer', 'lecturer.dashboard'],
    'student' => ['student', 'student.dashboard'],
    'kaprodi' => ['kaprodi', 'kaprodi.dashboard'],
]);

test('middleware role menolak akses lintas-role', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get('/admin/dashboard')->assertForbidden();
    $this->actingAs($student)->get('/kaprodi/dashboard')->assertForbidden();
});

test('role bisa akses dashboard miliknya sendiri', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->get('/admin/dashboard')->assertOk();
});

test('tamu diarahkan ke login saat akses dashboard', function () {
    $this->get('/admin/dashboard')->assertRedirect(route('login'));
});
