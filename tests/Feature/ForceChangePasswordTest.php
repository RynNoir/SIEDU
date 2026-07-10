<?php

use App\Models\User;

test('user dengan flag dipaksa ke halaman ganti password', function () {
    $user = User::factory()->admin()->create(['must_change_password' => true]);

    $this->actingAs($user)->get('/admin/dashboard')
        ->assertRedirect(route('password.change'));
});

test('halaman ganti password sendiri tidak ikut dialihkan', function () {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)->get(route('password.change'))->assertOk();
});

test('mengganti password mematikan flag dan mendarat di dashboard', function () {
    $user = User::factory()->admin()->create(['must_change_password' => true]);

    $response = $this->actingAs($user)->put(route('password.change.update'), [
        'password' => 'rahasia-baru-123',
        'password_confirmation' => 'rahasia-baru-123',
    ]);

    $response->assertRedirect(route('admin.dashboard'));
    expect($user->fresh()->must_change_password)->toBeFalse();
});

test('user tanpa flag tidak terpengaruh middleware', function () {
    $user = User::factory()->admin()->create(['must_change_password' => false]);

    $this->actingAs($user)->get('/admin/dashboard')->assertOk();
});
