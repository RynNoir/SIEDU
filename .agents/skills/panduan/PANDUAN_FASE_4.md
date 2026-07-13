# Panduan Fase 4 — Autentikasi, Role & Force-Change-Password (SIEDU)

Panduan ngoding manual untuk Fase 4 di [TODO.md](TODO.md). Referensi: [PRD.md](PRD.md) §3 (role), §8 (keamanan, `must_change_password`), §6.2–6.5 (redirect per role).

## Konteks & prasyarat

- Breeze (Blade) sudah terpasang sejak Fase 0. Login email+password sudah jalan.
- User model sudah punya `role` (enum), `must_change_password` (bool cast), dan helper `isAdmin()`/`isLecturer()`/`isStudent()`/`isKaprodi()`.
- `bootstrap/app.php` bagian `withMiddleware` masih kosong — di sinilah alias middleware didaftarkan.
- Data seed Fase 3 siap dipakai untuk uji manual: `admin@siedu.test`, `kaprodi.mi@siedu.test`, dst (password `password`). Kaprodi punya `must_change_password=true`.

## Keputusan desain (silakan koreksi bila tidak setuju)

1. **Self-registration dinonaktifkan** (PRD: semua akun dibuat admin). Route/controller/view/test `register` bawaan Breeze dihapus. Link "Register" di `welcome.blade.php` otomatis hilang karena sudah dibungkus `@if (Route::has('register'))`.
2. **Middleware `role`** berupa alias, dipakai `role:admin` atau `role:admin,kaprodi` (multi-role). Menolak dengan `403`.
3. **`EnsurePasswordChanged`** dipasang **global di grup `web`** (append) — otomatis berlaku di semua route. Saat user login punya `must_change_password=true`, semua request dialihkan ke halaman ganti password (kecuali route ganti-password itu sendiri & logout).
4. **Dashboard per role masih placeholder** di fase ini (`admin.dashboard`, `lecturer.dashboard`, `student.dashboard`, `kaprodi.dashboard`) — isinya diganti di Fase 5/7/8/9. Fase 4 fokus ke *routing, guard, dan redirect*-nya.
5. **Force-change tidak meminta password lama** — akun baru semuanya berpassword default `"password"` yang sudah diketahui, jadi cukup minta password baru + konfirmasi. (Kalau mau lebih ketat, tambahkan aturan `current_password` — dicatat di bawah.)

---

## Urutan Commit (7 commit)

Urutan penting: tiap langkah dipakai langkah berikutnya. `EnsurePasswordChanged` (commit 5) menunjuk route `password.change`, jadi halaman ganti password (commit 4) dibuat lebih dulu.

| # | Commit | Isi utama |
|---|---|---|
| 1 | Middleware `role` | `EnsureUserHasRole` + daftar alias di `bootstrap/app.php` |
| 2 | Dashboard per role | 4 route+view placeholder + `User::dashboardRoute()` |
| 3 | Redirect pasca-login | `AuthenticatedSessionController` + `/dashboard` pintar |
| 4 | Halaman ganti password | `ChangePasswordController` + route + view |
| 5 | Middleware force-change | `EnsurePasswordChanged` + append ke grup web |
| 6 | Nonaktifkan registrasi | hapus route/controller/view/test register |
| 7 | Feature test + TODO | test role/redirect/force-change + update checklist |

**Alur kerja tiap commit:** buat/edit file → `vendor/bin/pint --dirty --format agent` → uji (manual/test) → `git add` + `git commit`.

---

## Commit 1 — Middleware `role` (EnsureUserHasRole)

```bash
php artisan make:middleware EnsureUserHasRole --no-interaction
```

**`app/Http/Middleware/EnsureUserHasRole.php`** (ganti method `handle`):

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Izinkan hanya user dengan salah satu role yang diberikan.
     * Pemakaian: ->middleware('role:admin') atau 'role:admin,kaprodi'.
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if ($user === null || ! in_array($user->role->value, $roles, true)) {
            abort(403);
        }

        return $next($request);
    }
}
```

**Edit `bootstrap/app.php`** — daftarkan alias di `withMiddleware`:

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);
    })
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware/EnsureUserHasRole.php bootstrap/app.php
git commit -m "Fase 4: middleware role (EnsureUserHasRole)

Middleware alias 'role' membatasi akses route per role (mendukung
multi-role, mis. role:admin,kaprodi). Menolak dengan 403. Didaftarkan
di bootstrap/app.php.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — Dashboard per role (placeholder) + `User::dashboardRoute()`

**Tambahkan method ke `app/Models/User.php`** (di dalam class, setelah helper `isKaprodi()`):

```php
    /**
     * Nama route dashboard sesuai role (landing pasca-login).
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            Role::Admin => 'admin.dashboard',
            Role::Lecturer => 'lecturer.dashboard',
            Role::Student => 'student.dashboard',
            Role::Kaprodi => 'kaprodi.dashboard',
        };
    }
```

> `Role` sudah di-`use` di User.php dari Fase 2. `match` bersifat exhaustive — semua 4 case wajib ada, jadi kalau nanti tambah role baru PHP akan mengingatkan.

**Edit `routes/web.php`** — tambahkan grup route per role (di atas `require __DIR__.'/auth.php';`):

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:lecturer'])->prefix('lecturer')->name('lecturer.')->group(function () {
    Route::view('/dashboard', 'lecturer.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:student'])->prefix('student')->name('student.')->group(function () {
    Route::view('/dashboard', 'student.dashboard')->name('dashboard');
});

Route::middleware(['auth', 'role:kaprodi'])->prefix('kaprodi')->name('kaprodi.')->group(function () {
    Route::view('/dashboard', 'kaprodi.dashboard')->name('dashboard');
});
```

**Buat 4 view placeholder.** Template (ganti `JUDUL` & `KETERANGAN`):

```blade
<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">JUDUL</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg shadow-sm">
                <div class="p-6 text-ink">
                    Selamat datang, {{ auth()->user()->name }}. KETERANGAN
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
```

Buat file berikut dari template itu:

| File | JUDUL | KETERANGAN |
|---|---|---|
| `resources/views/admin/dashboard.blade.php` | Dashboard Admin | (Placeholder — master data diisi di Fase 5.) |
| `resources/views/lecturer/dashboard.blade.php` | Dashboard Dosen | (Placeholder — hasil evaluasi diisi di Fase 8.) |
| `resources/views/student/dashboard.blade.php` | Daftar Evaluasi | (Placeholder — form evaluasi diisi di Fase 7.) |
| `resources/views/kaprodi/dashboard.blade.php` | Dashboard Prodi | (Placeholder — agregasi diisi di Fase 9.) |

```bash
vendor/bin/pint --dirty --format agent
git add app/Models/User.php routes/web.php resources/views/admin/ resources/views/lecturer/ resources/views/student/ resources/views/kaprodi/
git commit -m "Fase 4: dashboard placeholder per role + User::dashboardRoute()

4 grup route ber-prefix & ber-guard role (admin/lecturer/student/
kaprodi) dengan view placeholder; dashboardRoute() memetakan role ->
nama route landing. Isi view diganti di fase modul masing-masing.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — Redirect pasca-login sesuai role

**Edit `app/Http/Controllers/Auth/AuthenticatedSessionController.php`** — method `store`:

```php
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        return redirect()->intended(route($request->user()->dashboardRoute()));
    }
```

**Edit `routes/web.php`** — ganti route `/dashboard` bawaan menjadi pengalih pintar (hapus `verified`):

```php
Route::get('/dashboard', function () {
    return redirect()->route(auth()->user()->dashboardRoute());
})->middleware('auth')->name('dashboard');
```

> `route('dashboard')` tetap ada (dipakai navigasi Breeze), tapi kini mengarahkan ke dashboard sesuai role. Login langsung mendarat di dashboard role (via `intended`, jadi kalau user tadinya menuju halaman terproteksi ia dikembalikan ke sana).

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Auth/AuthenticatedSessionController.php routes/web.php
git commit -m "Fase 4: redirect pasca-login sesuai role

store() mengarahkan ke route dashboard role via dashboardRoute();
/dashboard bawaan jadi pengalih pintar agar link navigasi Breeze tetap
valid. Menghapus middleware verified (email tidak diverifikasi karena
akun dibuat admin).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — Halaman & controller ganti password (force-change)

```bash
php artisan make:controller Auth/ChangePasswordController --no-interaction
```

**`app/Http/Controllers/Auth/ChangePasswordController.php`**:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ChangePasswordController extends Controller
{
    public function edit(): View
    {
        return view('auth.change-password');
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
            'must_change_password' => false,
        ]);

        return redirect()
            ->route($request->user()->dashboardRoute())
            ->with('status', 'Password berhasil diperbarui.');
    }
}
```

> Tidak meminta `current_password` (password default sudah diketahui). Kalau mau lebih ketat, tambahkan `'current_password' => ['required', 'current_password']` ke aturan validasi.

**Edit `routes/web.php`** — tambahkan ke grup `Route::middleware('auth')->group(...)` yang sudah ada (yang berisi profile), atau buat grup baru:

```php
use App\Http\Controllers\Auth\ChangePasswordController;

// ... di dalam grup middleware('auth'):
Route::get('password/change', [ChangePasswordController::class, 'edit'])->name('password.change');
Route::put('password/change', [ChangePasswordController::class, 'update'])->name('password.change.update');
```

**Buat `resources/views/auth/change-password.blade.php`**:

```blade
<x-guest-layout>
    <div class="mb-4 text-sm text-muted">
        Demi keamanan, Anda wajib mengganti password default sebelum melanjutkan.
    </div>

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" :value="'Password Baru'" />
            <x-text-input id="password" name="password" type="password"
                class="mt-1 block w-full" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Konfirmasi Password'" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button>{{ __('Simpan Password') }}</x-primary-button>
        </div>
    </form>

    {{-- Form logout terpisah (form tidak boleh bersarang) --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-muted underline">Keluar</button>
    </form>
</x-guest-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Auth/ChangePasswordController.php routes/web.php resources/views/auth/change-password.blade.php
git commit -m "Fase 4: halaman & controller ganti password (force-change)

Form ganti password baru (tanpa minta password lama, karena default
diketahui) yang men-set must_change_password=false lalu mengarahkan ke
dashboard role. View pakai guest-layout + tombol keluar.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 5 — Middleware force-change (EnsurePasswordChanged)

```bash
php artisan make:middleware EnsurePasswordChanged --no-interaction
```

**`app/Http/Middleware/EnsurePasswordChanged.php`** (ganti method `handle`):

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Paksa user dengan must_change_password=true ke halaman ganti password,
     * kecuali saat sedang mengakses halaman ganti password itu sendiri / logout.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null
            && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.change.update', 'logout')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
```

**Edit `bootstrap/app.php`** — append ke grup `web` (di dalam `withMiddleware`, setelah `alias`):

```php
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureUserHasRole::class,
        ]);

        $middleware->web(append: [
            \App\Http\Middleware\EnsurePasswordChanged::class,
        ]);
    })
```

> Dipasang global di grup web → berlaku otomatis di semua route web. Untuk tamu / user dengan flag `false`, middleware langsung meneruskan (tidak mengganggu).

**Uji manual** (opsional): login sebagai `kaprodi.mi@siedu.test` / `password` (flag-nya `true` dari seeder) → harus terlempar ke halaman ganti password; setelah ganti → mendarat di dashboard prodi.

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Middleware/EnsurePasswordChanged.php bootstrap/app.php
git commit -m "Fase 4: middleware force-change-password (EnsurePasswordChanged)

Dipasang global di grup web: user dengan must_change_password=true
dialihkan paksa ke halaman ganti password (kecuali route ganti-password
& logout). Tamu / flag false diteruskan tanpa efek.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 6 — Nonaktifkan self-registration (PRD: tidak ada self-registration)

**Edit `routes/auth.php`**:
- Hapus baris `use App\Http\Controllers\Auth\RegisteredUserController;`
- Hapus 2 route register di dalam grup `guest`:

```php
    Route::get('register', [RegisteredUserController::class, 'create'])
        ->name('register');

    Route::post('register', [RegisteredUserController::class, 'store']);
```

**Hapus file-file registrasi:**

```bash
rm app/Http/Controllers/Auth/RegisteredUserController.php
rm resources/views/auth/register.blade.php
rm tests/Feature/Auth/RegistrationTest.php
```

> Link "Register" di `welcome.blade.php` sudah dibungkus `@if (Route::has('register'))`, jadi otomatis hilang begitu route-nya tidak ada — tidak perlu diedit.

**Uji cepat:** `php artisan route:list --name=register` harus kosong; `php artisan test --compact` tidak boleh ada error "route register tidak ada".

```bash
vendor/bin/pint --dirty --format agent
git add routes/auth.php app/Http/Controllers/Auth/RegisteredUserController.php resources/views/auth/register.blade.php tests/Feature/Auth/RegistrationTest.php
git commit -m "Fase 4: nonaktifkan self-registration (PRD)

Semua akun dibuat admin — route/controller/view/test register bawaan
Breeze dihapus. Link register di welcome auto-hilang via
Route::has('register').

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

> Catatan git: karena ada file yang dihapus, `git add <path>` pada file terhapus akan men-stage penghapusannya. Alternatif: `git add -A routes/auth.php app/Http/Controllers/Auth resources/views/auth tests/Feature/Auth`.

---

## Commit 7 — Feature test + update TODO.md

### 7a. Perbarui test login bawaan yang terpengaruh

**Edit `tests/Feature/Auth/AuthenticationTest.php`** — test 'users can authenticate' sekarang mendarat di dashboard role. User factory default = student:

```php
test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('student.dashboard', absolute: false));
});
```

### 7b. Test akses role

```bash
php artisan make:test RoleAccessTest --pest --no-interaction
```

**`tests/Feature/RoleAccessTest.php`**:

```php
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
```

### 7c. Test force-change-password

```bash
php artisan make:test ForceChangePasswordTest --pest --no-interaction
```

**`tests/Feature/ForceChangePasswordTest.php`**:

```php
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
```

### 7d. Jalankan seluruh test

```bash
php artisan test --compact
```

Semua harus hijau (termasuk test Breeze lain yang tidak berubah). Kalau ada yang merah, perbaiki sebelum commit.

### 7e. Update TODO.md

Centang semua item Fase 4 jadi `[x]` dan update baris **Status project saat ini**, kira-kira:

> *Fase 0–4 selesai. Fase 4: middleware `role` + `EnsurePasswordChanged` (global web), dashboard placeholder per role, redirect pasca-login per role, halaman force-change-password, self-registration dinonaktifkan. Feature test role/redirect/force-change hijau. Siap lanjut Fase 5 (Modul Admin — Master Data CRUD).*

```bash
vendor/bin/pint --dirty --format agent
git add tests/ TODO.md
git commit -m "Fase 4: feature test auth/role/force-change + update TODO

Test: redirect login per role, role middleware menolak lintas-role,
force-change mengalihkan & mematikan flag. AuthenticationTest bawaan
disesuaikan ke dashboard role. Fase 4 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Lalu push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **`$user->role->value`** di middleware — `role` sudah enum, jadi ambil `.value` untuk banding dengan string `'admin'`.
- [ ] **`use Illuminate\Support\Facades\Route;`** sudah ada di `routes/web.php` (bawaan). Untuk `ChangePasswordController`, tambahkan `use` import controllernya di web.php.
- [ ] **`EnsurePasswordChanged` di-append SETELAH route `password.change` ada** (commit 4 sebelum 5) — kalau dibalik, login kaprodi (flag true) error route tak ditemukan.
- [ ] **Form logout jangan bersarang** di dalam form ganti password (HTML invalid) — taruh terpisah.
- [ ] **AuthenticationTest** perlu disesuaikan (user factory default = student → `student.dashboard`), kalau tidak test bawaan jadi merah.
- [ ] Jalankan **`vendor/bin/pint --dirty --format agent`** sebelum tiap commit.
- [ ] Setelah menghapus route register, jalankan `php artisan route:list` untuk memastikan tidak ada referensi `route('register')` yang tersisa.

> File panduan ini boleh dihapus setelah Fase 4 kelar (opsional): `git rm PANDUAN_FASE_4.md`.
