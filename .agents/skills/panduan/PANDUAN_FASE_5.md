# Panduan Fase 5 — Modul Admin (Master Data CRUD) · Bagian 1: Fondasi + CRUD Sederhana

Panduan ngoding manual untuk Fase 5 di [TODO.md](TODO.md). **Bagian 1 ini** mencakup: komponen Blade GUIDELINE + layout admin sidebar + 3 CRUD paling sederhana (**study_programs, courses, evaluation_questions**). CRUD kompleks (class_groups, lecturers, students, evaluation_periods, course_class_assignments) menyusul di **Bagian 2** setelah bagian ini jalan & lolos test.

Referensi wajib:
- [PRD.md](PRD.md) §4 (skema), §7.2 (semester 7/8 hanya D4).
- [GUIDELINE.md](GUIDELINE.md) §3 (tipografi), §4.1/§4.4 (layout & tabel), §6 (komponen), §7 (spacing), §12 (copy).
- Skill aktif: **tailwindcss-development** (Tailwind v4 + token), **laravel-best-practices** (FormRequest, `$request->validated()`, `Route::resource`, eager-load).

## Prinsip yang dipegang (dari GUIDELINE + skill)

- **Token, bukan warna default**: pakai `bg-accent`, `text-ink`, `border-border`, `bg-surface`, `bg-canvas`, `text-muted`, `rounded-card`, `rounded-input`, `font-mono` — bukan `bg-indigo-500`/`text-gray-700`. Token ini sudah ada di `resources/css/app.css` (Fase 0).
- **Accent teal hanya untuk elemen interaktif** (tombol/link/tab aktif). **Amber (`rating`) hanya untuk rating** (belum dipakai di fase ini). **Warna status hanya untuk badge**.
- **FormRequest** untuk semua validasi; controller memakai `$request->validated()`, tidak pernah `$request->all()`.
- **`Route::resource`** + implicit route-model-binding; method controller ringkas.
- Kolom identifier (kode prodi/MK, NIM) pakai `font-mono` (GUIDELINE §3.2).

---

## Peta Commit — Bagian 1 (6 commit)

| # | Commit | Isi |
|---|---|---|
| 1 | Komponen Blade GUIDELINE | `x-button`, `x-badge-status`, `x-table`, `x-card`, `x-empty-state`, `x-select` + restyle input Breeze ke token |
| 2 | Layout admin (sidebar) | `x-admin-layout` (GUIDELINE §4.1) + admin dashboard pakai layout baru |
| 3 | CRUD study_programs | controller + FormRequest + views |
| 4 | CRUD courses | + validasi §7.2 (semester 7/8 hanya D4) |
| 5 | CRUD evaluation_questions | kategori/urutan/aktif-nonaktif |
| 6 | Feature test + update TODO | test CRUD + aturan jenjang |

---

## Commit 1 — Komponen Blade sesuai GUIDELINE

Semua di bawah adalah **anonymous component** (tanpa class PHP), taruh di `resources/views/components/`.

### `resources/views/components/button.blade.php` (GUIDELINE §6.1)

```blade
@props(['variant' => 'primary', 'href' => null, 'type' => 'submit'])

@php
    $base = 'inline-flex items-center justify-center gap-2 rounded-input px-5 py-2.5 text-sm font-medium transition duration-150 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2';
    $variants = [
        'primary' => 'bg-accent text-white hover:brightness-95',
        'secondary' => 'border border-border text-ink hover:bg-accent-soft',
        'destructive' => 'border border-danger text-danger hover:bg-danger/10',
        'disabled' => 'bg-border text-muted cursor-not-allowed',
    ];
    $classes = $base.' '.($variants[$variant] ?? $variants['primary']);
@endphp

@if ($href && $variant !== 'disabled')
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button @if ($variant === 'disabled') disabled @endif {{ $attributes->merge(['type' => $type, 'class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
```

### `resources/views/components/badge-status.blade.php` (GUIDELINE §6.4)

```blade
@props(['status'])

@php
    $key = $status instanceof \BackedEnum ? $status->value : $status;
    $map = [
        'aktif' => ['label' => 'Aktif', 'class' => 'bg-success/15 text-success'],
        'cuti' => ['label' => 'Cuti', 'class' => 'bg-warning/15 text-warning'],
        'DO' => ['label' => 'DO', 'class' => 'bg-danger/15 text-danger'],
        'lulus' => ['label' => 'Lulus', 'class' => 'bg-muted/15 text-muted'],
        'draft' => ['label' => 'Draft', 'class' => 'bg-muted/15 text-muted'],
        'open' => ['label' => 'Open', 'class' => 'bg-accent-soft text-accent'],
        'closed' => ['label' => 'Closed', 'class' => 'bg-muted/15 text-muted'],
    ];
    $s = $map[$key] ?? ['label' => ucfirst((string) $key), 'class' => 'bg-muted/15 text-muted'];
@endphp

{{-- Selalu ada label teks (bukan warna saja) demi aksesibilitas GUIDELINE §9 --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium '.$s['class']]) }}>
    <span class="size-1.5 rounded-full bg-current"></span>
    {{ $s['label'] }}
</span>
```

### `resources/views/components/table.blade.php` (GUIDELINE §6.3)

```blade
{{-- Header bg-canvas, border 1px, tanpa zebra-stripe. Slot 'head' untuk <th>, slot default untuk <tr> body. --}}
<div class="overflow-x-auto rounded-card border border-border bg-surface">
    <table class="min-w-full border-collapse text-sm">
        <thead class="bg-canvas">
            <tr class="text-left">
                {{ $head }}
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            {{ $slot }}
        </tbody>
    </table>
</div>
```

### `resources/views/components/card.blade.php`

```blade
<div {{ $attributes->merge(['class' => 'rounded-card border border-border bg-surface p-6']) }}>
    {{ $slot }}
</div>
```

### `resources/views/components/empty-state.blade.php` (GUIDELINE §6.7)

```blade
@props(['message' => 'Belum ada data.'])

<div class="flex flex-col items-center justify-center gap-3 rounded-card border border-dashed border-border bg-surface px-6 py-12 text-center">
    <p class="text-sm text-muted">{{ $message }}</p>
    {{ $slot }}
</div>
```

### `resources/views/components/select.blade.php` (GUIDELINE §6.2)

```blade
@props(['disabled' => false])

<select @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded-input border-border bg-surface text-ink text-sm shadow-sm focus:border-accent focus:ring-accent disabled:bg-canvas disabled:text-muted']) }}>
    {{ $slot }}
</select>
```

### Restyle 3 komponen input Breeze ke token GUIDELINE §6.2

**`resources/views/components/text-input.blade.php`**:

```blade
@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded-input border-border bg-surface text-ink text-sm shadow-sm focus:border-accent focus:ring-accent placeholder:text-muted disabled:bg-canvas disabled:text-muted']) }}>
```

**`resources/views/components/input-label.blade.php`**:

```blade
@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-ink']) }}>
    {{ $value ?? $slot }}
</label>
```

**`resources/views/components/input-error.blade.php`**:

```blade
@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'space-y-1 text-sm text-danger']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
```

> Restyle ini juga membuat halaman login/ganti-password (Fase 4) ikut memakai warna GUIDELINE — konsisten, tidak masalah.

```bash
vendor/bin/pint --dirty --format agent   # (tidak ada PHP baru, tapi aman dijalankan)
npm run build                            # pastikan class token ter-compile tanpa error
git add resources/views/components/
git commit -m "Fase 5: komponen Blade GUIDELINE (button/badge/table/card/empty/select)

Komponen reusable sesuai GUIDELINE.md §6 (token warna, radius-input,
badge pill + label teks, tabel border tanpa zebra). Input Breeze
di-restyle ke token (§6.2). Dipakai ulang di seluruh CRUD Fase 5.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — Layout admin dengan sidebar (GUIDELINE §4.1)

Breeze pakai top-nav; GUIDELINE mau **sidebar kiri tetap** untuk admin. Buat anonymous component `x-admin-layout`.

**`resources/views/components/admin-layout.blade.php`**:

```blade
@props(['header' => null])

@php
    $navItems = [
        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'pattern' => 'admin.dashboard'],
        ['route' => 'admin.study-programs.index', 'label' => 'Program Studi', 'pattern' => 'admin.study-programs.*'],
        ['route' => 'admin.class-groups.index', 'label' => 'Kelas', 'pattern' => 'admin.class-groups.*'],
        ['route' => 'admin.courses.index', 'label' => 'Mata Kuliah', 'pattern' => 'admin.courses.*'],
        ['route' => 'admin.lecturers.index', 'label' => 'Dosen', 'pattern' => 'admin.lecturers.*'],
        ['route' => 'admin.students.index', 'label' => 'Mahasiswa', 'pattern' => 'admin.students.*'],
        ['route' => 'admin.evaluation-periods.index', 'label' => 'Periode Evaluasi', 'pattern' => 'admin.evaluation-periods.*'],
        ['route' => 'admin.evaluation-questions.index', 'label' => 'Pertanyaan', 'pattern' => 'admin.evaluation-questions.*'],
        ['route' => 'admin.course-class-assignments.index', 'label' => 'Penugasan Dosen', 'pattern' => 'admin.course-class-assignments.*'],
    ];
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-canvas text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        {{-- Overlay mobile --}}
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-20 bg-ink/40 lg:hidden"></div>

        {{-- Sidebar --}}
        <aside x-cloak
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 transform border-r border-border bg-surface transition-transform lg:static lg:translate-x-0">
            <div class="flex h-16 items-center border-b border-border px-6">
                <span class="font-display text-lg font-semibold text-ink">SIEDU</span>
                <span class="ml-2 text-xs text-muted">Admin</span>
            </div>
            <nav class="space-y-1 p-4">
                @foreach ($navItems as $item)
                    @if (Route::has($item['route']))
                        <a href="{{ route($item['route']) }}"
                            class="block rounded-input px-3 py-2 text-sm {{ request()->routeIs($item['pattern']) ? 'bg-accent-soft font-medium text-accent' : 'text-ink hover:bg-canvas' }}">
                            {{ $item['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>
        </aside>

        {{-- Konten --}}
        <div class="flex min-w-0 flex-1 flex-col">
            <header class="flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-6">
                <div class="flex items-center gap-3">
                    <button @click="sidebarOpen = !sidebarOpen" class="text-muted lg:hidden" aria-label="Menu">
                        <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <div class="font-display text-base font-semibold">{{ $header ?? '' }}</div>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-sm text-muted hover:text-ink">
                        {{ auth()->user()->name }} · Keluar
                    </button>
                </form>
            </header>

            <main class="p-4 lg:p-6">
                @if (session('success'))
                    <div class="mb-4 rounded-card border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                        {{ session('success') }}
                    </div>
                @endif

                {{ $slot }}
            </main>
        </div>
    </div>
</body>
</html>
```

> `x-cloak` menyembunyikan elemen sebelum Alpine siap. Tambahkan sekali di `app.css` agar bekerja:

Tambahkan di **`resources/css/app.css`** (paling bawah, di luar `@theme`):

```css
[x-cloak] { display: none !important; }
```

**Ubah `resources/views/admin/dashboard.blade.php`** agar pakai layout baru:

```blade
<x-admin-layout header="Dashboard Admin">
    <x-card>
        <p class="text-ink">Selamat datang, {{ auth()->user()->name }}.</p>
        <p class="mt-1 text-sm text-muted">Kelola master data lewat menu di samping.</p>
    </x-card>
</x-admin-layout>
```

```bash
npm run build
git add resources/views/components/admin-layout.blade.php resources/views/admin/dashboard.blade.php resources/css/app.css
git commit -m "Fase 5: layout admin sidebar (GUIDELINE §4.1)

x-admin-layout: sidebar kiri tetap (desktop) + toggle mobile (Alpine),
nav per-item ber-guard Route::has() agar link muncul otomatis saat
CRUD-nya dibuat. Admin dashboard memakai layout baru.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — CRUD study_programs

```bash
php artisan make:controller Admin/StudyProgramController --no-interaction
php artisan make:request Admin/StudyProgramRequest --no-interaction
```

**`app/Http/Requests/Admin/StudyProgramRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\DegreeLevel;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudyProgramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // sudah dijaga middleware role:admin
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required', 'string', 'max:10',
                Rule::unique('study_programs', 'code')->ignore($this->route('study_program')),
            ],
            'degree_level' => ['required', Rule::enum(DegreeLevel::class)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.unique' => 'Kode prodi sudah dipakai.',
        ];
    }
}
```

**`app/Http/Controllers/Admin/StudyProgramController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\DegreeLevel;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudyProgramRequest;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StudyProgramController extends Controller
{
    public function index(): View
    {
        $studyPrograms = StudyProgram::orderBy('code')->paginate(15);

        return view('admin.study-programs.index', compact('studyPrograms'));
    }

    public function create(): View
    {
        return view('admin.study-programs.create');
    }

    public function store(StudyProgramRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['total_semesters'] = DegreeLevel::from($data['degree_level'])->totalSemesters();

        StudyProgram::create($data);

        return redirect()->route('admin.study-programs.index')
            ->with('success', 'Program studi ditambahkan.');
    }

    public function edit(StudyProgram $studyProgram): View
    {
        return view('admin.study-programs.edit', compact('studyProgram'));
    }

    public function update(StudyProgramRequest $request, StudyProgram $studyProgram): RedirectResponse
    {
        $data = $request->validated();
        $data['total_semesters'] = DegreeLevel::from($data['degree_level'])->totalSemesters();

        $studyProgram->update($data);

        return redirect()->route('admin.study-programs.index')
            ->with('success', 'Program studi diperbarui.');
    }

    public function destroy(StudyProgram $studyProgram): RedirectResponse
    {
        $studyProgram->delete();

        return redirect()->route('admin.study-programs.index')
            ->with('success', 'Program studi dihapus.');
    }
}
```

> `total_semesters` diturunkan otomatis dari jenjang (D3=6, D4=8) via helper enum — tidak perlu input manual (PRD §2.1).

**Route** — edit `routes/web.php`, tambah di grup admin (setelah baris `Route::view('/dashboard'...)`), plus import controller di atas:

```php
use App\Http\Controllers\Admin\StudyProgramController;

// di dalam grup admin:
Route::resource('study-programs', StudyProgramController::class)->except('show');
```

**Views** — buat folder `resources/views/admin/study-programs/`.

`index.blade.php`:

```blade
<x-admin-layout header="Program Studi">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $studyPrograms->total() }} program studi</p>
        <x-button :href="route('admin.study-programs.create')">Tambah Prodi</x-button>
    </div>

    @if ($studyPrograms->isEmpty())
        <x-empty-state message="Belum ada program studi. Tambahkan prodi pertama untuk memulai." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kode</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Jenjang</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Semester</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($studyPrograms as $prodi)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $prodi->code }}</td>
                    <td class="px-4 py-3 text-ink">{{ $prodi->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $prodi->degree_level->value }}</td>
                    <td class="px-4 py-3 text-muted">{{ $prodi->total_semesters }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.study-programs.edit', $prodi)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.study-programs.destroy', $prodi) }}"
                                onsubmit="return confirm('Hapus prodi {{ $prodi->code }}?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $studyPrograms->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php` (partial dipakai create & edit):

```blade
@php $editing = isset($studyProgram) && $studyProgram->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.study-programs.update', $studyProgram) : route('admin.study-programs.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="code" :value="'Kode Prodi'" />
            <x-text-input id="code" name="code" class="mt-1 font-mono"
                :value="old('code', $studyProgram->code ?? '')" required />
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="name" :value="'Nama Prodi'" />
            <x-text-input id="name" name="name" class="mt-1"
                :value="old('name', $studyProgram->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="degree_level" :value="'Jenjang'" />
            <x-select id="degree_level" name="degree_level" class="mt-1" required>
                @foreach (\App\Enums\DegreeLevel::cases() as $level)
                    <option value="{{ $level->value }}"
                        @selected(old('degree_level', $studyProgram->degree_level->value ?? '') === $level->value)>
                        {{ $level->value }} ({{ $level->totalSemesters() }} semester)
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('degree_level')" class="mt-1" />
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Prodi' }}</x-button>
        <x-button variant="secondary" :href="route('admin.study-programs.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php`:

```blade
<x-admin-layout header="Tambah Program Studi">
    <x-card class="max-w-xl">
        @include('admin.study-programs._form')
    </x-card>
</x-admin-layout>
```

`edit.blade.php`:

```blade
<x-admin-layout header="Edit Program Studi">
    <x-card class="max-w-xl">
        @include('admin.study-programs._form')
    </x-card>
</x-admin-layout>
```

Uji manual: login admin → menu "Program Studi" muncul di sidebar → tambah/edit/hapus.

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/StudyProgramController.php app/Http/Requests/Admin/StudyProgramRequest.php routes/web.php resources/views/admin/study-programs/
git commit -m "Fase 5: CRUD study_programs

Resource controller + FormRequest (kode unik, jenjang enum).
total_semesters diturunkan otomatis dari jenjang. Views pakai komponen
GUIDELINE (tabel, tombol, empty state). Kolom kode pakai font-mono.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — CRUD courses (+ validasi §7.2)

```bash
php artisan make:controller Admin/CourseController --no-interaction
php artisan make:request Admin/CourseRequest --no-interaction
```

**`app/Http/Requests/Admin/CourseRequest.php`** — perhatikan `after()` untuk aturan §7.2:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\DegreeLevel;
use App\Models\StudyProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:20'],
            'semester' => ['required', 'integer', 'between:1,8'],
            'credit_hours' => ['required', 'integer', 'between:1,6'],
        ];
    }

    /**
     * Aturan §7.2: mata kuliah semester 7/8 hanya valid untuk prodi D4.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $prodi = StudyProgram::find($this->input('study_program_id'));

                if ($prodi && (int) $this->input('semester') >= 7 && $prodi->degree_level !== DegreeLevel::D4) {
                    $validator->errors()->add('semester', 'Semester 7–8 hanya untuk prodi D4 (TRPL, ANIM).');
                }
            },
        ];
    }
}
```

**`app/Http/Controllers/Admin/CourseController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseRequest;
use App\Models\Course;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function index(): View
    {
        $courses = Course::with('studyProgram')
            ->orderBy('study_program_id')
            ->orderBy('semester')
            ->paginate(15);

        return view('admin.courses.index', compact('courses'));
    }

    public function create(): View
    {
        return view('admin.courses.create', ['studyPrograms' => StudyProgram::orderBy('code')->get()]);
    }

    public function store(CourseRequest $request): RedirectResponse
    {
        Course::create($request->validated());

        return redirect()->route('admin.courses.index')->with('success', 'Mata kuliah ditambahkan.');
    }

    public function edit(Course $course): View
    {
        return view('admin.courses.edit', [
            'course' => $course,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
        ]);
    }

    public function update(CourseRequest $request, Course $course): RedirectResponse
    {
        $course->update($request->validated());

        return redirect()->route('admin.courses.index')->with('success', 'Mata kuliah diperbarui.');
    }

    public function destroy(Course $course): RedirectResponse
    {
        $course->delete();

        return redirect()->route('admin.courses.index')->with('success', 'Mata kuliah dihapus.');
    }
}
```

> `index` memakai `with('studyProgram')` (eager load) untuk mencegah N+1 saat menampilkan nama prodi (laravel-best-practices §1).

**Route** (`routes/web.php`, grup admin + import):

```php
use App\Http\Controllers\Admin\CourseController;

Route::resource('courses', CourseController::class)->except('show');
```

**Views** — `resources/views/admin/courses/`.

`index.blade.php`:

```blade
<x-admin-layout header="Mata Kuliah">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $courses->total() }} mata kuliah</p>
        <x-button :href="route('admin.courses.create')">Tambah Mata Kuliah</x-button>
    </div>

    @if ($courses->isEmpty())
        <x-empty-state message="Belum ada mata kuliah. Tambahkan kurikulum paket per prodi." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kode</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Sem</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">SKS</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($courses as $course)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $course->code }}</td>
                    <td class="px-4 py-3 text-ink">{{ $course->name }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $course->studyProgram->code }}</td>
                    <td class="px-4 py-3 text-muted">{{ $course->semester }}</td>
                    <td class="px-4 py-3 text-muted">{{ $course->credit_hours }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.courses.edit', $course)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.courses.destroy', $course) }}"
                                onsubmit="return confirm('Hapus mata kuliah {{ $course->code }}?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $courses->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($course) && $course->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.courses.update', $course) : route('admin.courses.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="study_program_id" :value="'Program Studi'" />
            <x-select id="study_program_id" name="study_program_id" class="mt-1" required>
                <option value="">— pilih prodi —</option>
                @foreach ($studyPrograms as $prodi)
                    <option value="{{ $prodi->id }}"
                        @selected((int) old('study_program_id', $course->study_program_id ?? 0) === $prodi->id)>
                        {{ $prodi->code }} — {{ $prodi->name }} ({{ $prodi->degree_level->value }})
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('study_program_id')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="code" :value="'Kode MK'" />
            <x-text-input id="code" name="code" class="mt-1 font-mono"
                :value="old('code', $course->code ?? '')" required />
            <x-input-error :messages="$errors->get('code')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="name" :value="'Nama MK'" />
            <x-text-input id="name" name="name" class="mt-1"
                :value="old('name', $course->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="semester" :value="'Semester (1–8)'" />
                <x-text-input id="semester" name="semester" type="number" min="1" max="8" class="mt-1"
                    :value="old('semester', $course->semester ?? '')" required />
                <x-input-error :messages="$errors->get('semester')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="credit_hours" :value="'SKS'" />
                <x-text-input id="credit_hours" name="credit_hours" type="number" min="1" max="6" class="mt-1"
                    :value="old('credit_hours', $course->credit_hours ?? '')" required />
                <x-input-error :messages="$errors->get('credit_hours')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Mata Kuliah' }}</x-button>
        <x-button variant="secondary" :href="route('admin.courses.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php`:

```blade
<x-admin-layout header="Tambah Mata Kuliah">
    <x-card class="max-w-xl">
        @include('admin.courses._form')
    </x-card>
</x-admin-layout>
```

`edit.blade.php`:

```blade
<x-admin-layout header="Edit Mata Kuliah">
    <x-card class="max-w-xl">
        @include('admin.courses._form')
    </x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/CourseController.php app/Http/Requests/Admin/CourseRequest.php routes/web.php resources/views/admin/courses/
git commit -m "Fase 5: CRUD courses + validasi jenjang (PRD §7.2)

Resource controller + FormRequest; after() menegakkan semester 7-8
hanya untuk prodi D4. index eager-load studyProgram (cegah N+1).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 5 — CRUD evaluation_questions

```bash
php artisan make:controller Admin/EvaluationQuestionController --no-interaction
php artisan make:request Admin/EvaluationQuestionRequest --no-interaction
```

**`app/Http/Requests/Admin/EvaluationQuestionRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class EvaluationQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'category' => ['required', 'string', 'max:255'],
            'question_text' => ['required', 'string'],
            'order_number' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        // checkbox tak dicentang tidak terkirim → set false
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}
```

**`app/Http/Controllers/Admin/EvaluationQuestionController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationQuestionRequest;
use App\Models\EvaluationQuestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationQuestionController extends Controller
{
    public function index(): View
    {
        $questions = EvaluationQuestion::orderBy('order_number')->paginate(20);

        return view('admin.evaluation-questions.index', compact('questions'));
    }

    public function create(): View
    {
        return view('admin.evaluation-questions.create');
    }

    public function store(EvaluationQuestionRequest $request): RedirectResponse
    {
        EvaluationQuestion::create($request->validated());

        return redirect()->route('admin.evaluation-questions.index')->with('success', 'Pertanyaan ditambahkan.');
    }

    public function edit(EvaluationQuestion $evaluationQuestion): View
    {
        return view('admin.evaluation-questions.edit', ['question' => $evaluationQuestion]);
    }

    public function update(EvaluationQuestionRequest $request, EvaluationQuestion $evaluationQuestion): RedirectResponse
    {
        $evaluationQuestion->update($request->validated());

        return redirect()->route('admin.evaluation-questions.index')->with('success', 'Pertanyaan diperbarui.');
    }

    public function destroy(EvaluationQuestion $evaluationQuestion): RedirectResponse
    {
        $evaluationQuestion->delete();

        return redirect()->route('admin.evaluation-questions.index')->with('success', 'Pertanyaan dihapus.');
    }
}
```

**Route** (`routes/web.php`, grup admin + import):

```php
use App\Http\Controllers\Admin\EvaluationQuestionController;

Route::resource('evaluation-questions', EvaluationQuestionController::class)->except('show');
```

**Views** — `resources/views/admin/evaluation-questions/`.

`index.blade.php`:

```blade
<x-admin-layout header="Pertanyaan Kuesioner">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $questions->total() }} pertanyaan</p>
        <x-button :href="route('admin.evaluation-questions.create')">Tambah Pertanyaan</x-button>
    </div>

    @if ($questions->isEmpty())
        <x-empty-state message="Belum ada pertanyaan. Tambahkan pertanyaan kuesioner evaluasi." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">#</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kategori</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Pertanyaan</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($questions as $question)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-muted">{{ $question->order_number }}</td>
                    <td class="px-4 py-3 text-ink">{{ $question->category }}</td>
                    <td class="max-w-md px-4 py-3 text-muted">{{ $question->question_text }}</td>
                    <td class="px-4 py-3">
                        @if ($question->is_active)
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-medium text-success">Aktif</span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-muted/15 px-2.5 py-0.5 text-xs font-medium text-muted">Nonaktif</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.evaluation-questions.edit', $question)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.evaluation-questions.destroy', $question) }}"
                                onsubmit="return confirm('Hapus pertanyaan ini?')">
                                @csrf
                                @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $questions->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($question) && $question->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.evaluation-questions.update', $question) : route('admin.evaluation-questions.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="category" :value="'Kategori'" />
            <x-text-input id="category" name="category" class="mt-1"
                :value="old('category', $question->category ?? '')" required
                placeholder="mis. Penguasaan & Penyampaian Materi" />
            <x-input-error :messages="$errors->get('category')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="question_text" :value="'Pertanyaan'" />
            <textarea id="question_text" name="question_text" rows="3" required
                class="mt-1 w-full rounded-input border-border bg-surface text-ink text-sm shadow-sm focus:border-accent focus:ring-accent"
                placeholder="Bagaimana penilaian Anda terhadap ...?">{{ old('question_text', $question->question_text ?? '') }}</textarea>
            <x-input-error :messages="$errors->get('question_text')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="order_number" :value="'Urutan'" />
            <x-text-input id="order_number" name="order_number" type="number" min="1" class="mt-1 w-32"
                :value="old('order_number', $question->order_number ?? '')" required />
            <x-input-error :messages="$errors->get('order_number')" class="mt-1" />
        </div>

        <label class="flex items-center gap-2">
            <input type="checkbox" name="is_active" value="1"
                @checked(old('is_active', $question->is_active ?? true))
                class="rounded border-border text-accent focus:ring-accent">
            <span class="text-sm text-ink">Aktif (ditampilkan di kuesioner)</span>
        </label>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Pertanyaan' }}</x-button>
        <x-button variant="secondary" :href="route('admin.evaluation-questions.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php`:

```blade
<x-admin-layout header="Tambah Pertanyaan">
    <x-card class="max-w-2xl">
        @include('admin.evaluation-questions._form')
    </x-card>
</x-admin-layout>
```

`edit.blade.php`:

```blade
<x-admin-layout header="Edit Pertanyaan">
    <x-card class="max-w-2xl">
        @include('admin.evaluation-questions._form')
    </x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/EvaluationQuestionController.php app/Http/Requests/Admin/EvaluationQuestionRequest.php routes/web.php resources/views/admin/evaluation-questions/
git commit -m "Fase 5: CRUD evaluation_questions

Resource controller + FormRequest (kategori/urutan/aktif). Checkbox
is_active dinormalkan di prepareForValidation.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 6 — Feature test + update TODO (parsial)

```bash
php artisan make:test Admin/StudyProgramCrudTest --pest --no-interaction
php artisan make:test Admin/CourseValidationTest --pest --no-interaction
```

**`tests/Feature/Admin/StudyProgramCrudTest.php`**:

```php
<?php

use App\Models\StudyProgram;
use App\Models\User;

test('non-admin ditolak dari CRUD prodi', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)->get(route('admin.study-programs.index'))->assertForbidden();
});

test('admin melihat daftar prodi', function () {
    $admin = User::factory()->admin()->create();
    StudyProgram::factory()->create(['code' => 'MI', 'name' => 'Manajemen Informatika']);

    $this->actingAs($admin)->get(route('admin.study-programs.index'))
        ->assertOk()
        ->assertSee('MI');
});

test('admin menambah prodi dan total_semesters diturunkan dari jenjang', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)->post(route('admin.study-programs.store'), [
        'code' => 'TRPL',
        'name' => 'Teknologi Rekayasa Perangkat Lunak',
        'degree_level' => 'D4',
    ])->assertRedirect(route('admin.study-programs.index'));

    $this->assertDatabaseHas('study_programs', [
        'code' => 'TRPL',
        'degree_level' => 'D4',
        'total_semesters' => 8,
    ]);
});

test('kode prodi wajib unik', function () {
    $admin = User::factory()->admin()->create();
    StudyProgram::factory()->create(['code' => 'MI']);

    $this->actingAs($admin)->post(route('admin.study-programs.store'), [
        'code' => 'MI',
        'name' => 'Duplikat',
        'degree_level' => 'D3',
    ])->assertSessionHasErrors('code');
});

test('admin menghapus prodi', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create();

    $this->actingAs($admin)->delete(route('admin.study-programs.destroy', $prodi))
        ->assertRedirect(route('admin.study-programs.index'));

    $this->assertModelMissing($prodi);
});
```

**`tests/Feature/Admin/CourseValidationTest.php`**:

```php
<?php

use App\Models\StudyProgram;
use App\Models\User;

test('mata kuliah semester 7 ditolak untuk prodi D3', function () {
    $admin = User::factory()->admin()->create();
    $d3 = StudyProgram::factory()->create(['degree_level' => 'D3', 'total_semesters' => 6]);

    $this->actingAs($admin)->post(route('admin.courses.store'), [
        'study_program_id' => $d3->id,
        'code' => 'X701',
        'name' => 'MK Semester 7',
        'semester' => 7,
        'credit_hours' => 3,
    ])->assertSessionHasErrors('semester');

    $this->assertDatabaseCount('courses', 0);
});

test('mata kuliah semester 7 diterima untuk prodi D4', function () {
    $admin = User::factory()->admin()->create();
    $d4 = StudyProgram::factory()->create(['degree_level' => 'D4', 'total_semesters' => 8]);

    $this->actingAs($admin)->post(route('admin.courses.store'), [
        'study_program_id' => $d4->id,
        'code' => 'Y701',
        'name' => 'MK Semester 7',
        'semester' => 7,
        'credit_hours' => 3,
    ])->assertRedirect(route('admin.courses.index'));

    $this->assertDatabaseHas('courses', ['code' => 'Y701', 'semester' => 7]);
});
```

Jalankan:

```bash
php artisan test --compact
```

Semua harus hijau. Lalu **update TODO.md** — centang item Fase 5 yang **sudah** selesai (komponen Blade, CRUD study_programs, courses, evaluation_questions), biarkan sisanya. Update baris status kira-kira:

> *Fase 5 sedang berjalan (Bagian 1 selesai): komponen Blade GUIDELINE, layout admin sidebar, CRUD study_programs/courses/evaluation_questions + test. Berikutnya Bagian 2: class_groups, lecturers, students, evaluation_periods, course_class_assignments.*

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Admin/ TODO.md
git commit -m "Fase 5: feature test CRUD prodi/courses + aturan jenjang §7.2

Test akses non-admin ditolak, CRUD prodi, unik kode, derivasi
total_semesters, dan validasi semester 7-8 hanya D4. Bagian 1 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Checklist hal yang mudah terlewat

- [ ] **`use` controller di `routes/web.php`** — tiap `Route::resource` butuh import controllernya (jebakan yang sama seperti Fase 4).
- [ ] **`x-cloak` CSS** harus ditambahkan di `app.css`, kalau tidak sidebar mobile berkedip saat load.
- [ ] **`npm run build`** (atau `npm run dev` berjalan) tiap kali menambah class Tailwind baru, agar ter-compile.
- [ ] **`->except('show')`** pada resource — kita tidak bikin halaman show, cukup index+form.
- [ ] **Eager load** (`with('studyProgram')`) di index yang menampilkan relasi, cegah N+1.
- [ ] **Checkbox `is_active`** dinormalkan via `prepareForValidation` (checkbox tak dicentang tidak terkirim).
- [ ] Jalankan **`vendor/bin/pint --dirty --format agent`** sebelum tiap commit.

## Setelah Bagian 1 selesai

Beri tahu saya, dan saya lanjutkan **Bagian 2** (CRUD kompleks) dengan format sama:
- **class_groups** (auto-generate `class_code`, unik per academic_year)
- **lecturers** & **students** (buat akun `user` sekaligus, `created_by`, validasi konsistensi semester ↔ year_level §7.1, tabel mahasiswa dengan search+filter §4.4)
- **evaluation_periods** (aksi buka/tutup + tegakkan periode tunggal via `activate()`)
- **course_class_assignments** (team teaching, unique 4-kolom, validasi §7.1/§7.2)

> File panduan ini boleh dihapus setelah Fase 5 kelar (opsional): `git rm PANDUAN_FASE_5.md`.
