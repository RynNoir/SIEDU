# Panduan Fase 5 — Modul Admin · Bagian 2: CRUD Kompleks

Lanjutan [PANDUAN_FASE_5.md](PANDUAN_FASE_5.md). Bagian ini: **class_groups, lecturers, students, evaluation_periods, course_class_assignments** — CRUD dengan business logic (buat akun user, auto class_code, periode tunggal, team teaching, validasi §7.1/§7.2).

Referensi: [PRD.md](PRD.md) §2.2 (kode kelas), §4.4 (team teaching), §6.2 (periode tunggal), §7.1 (semester↔year_level), §7.2 (jenjang), §8 (password default). [GUIDELINE.md](GUIDELINE.md) §4.4 (tabel mahasiswa search+filter), §6.4 (badge), §6.6 (filter chip), §12 (copy). Skill: **laravel-best-practices**, **tailwindcss-development**.

> ⚠️ **Pelajaran dari bagian sebelumnya**: setiap kali pakai `Rule::`, `DB::`, `Hash::`, `Str::` dst, **pastikan `use`-nya ada di atas file**. Blok import di panduan ini sudah lengkap — salin apa adanya.

Prasyarat: fondasi (komponen + `x-admin-layout`) & CRUD Bagian 1 sudah jalan. Sidebar sudah menyiapkan link untuk semua CRUD ini (ber-guard `Route::has()`), jadi link muncul otomatis saat route-nya dibuat.

---

## Peta Commit — Bagian 2 (6 commit)

| # | Commit | Business logic utama |
|---|---|---|
| 1 | CRUD class_groups | auto-generate `class_code`, unik per academic_year, year ≤ max prodi |
| 2 | CRUD lecturers | buat akun `user` (role=lecturer, password default) dalam transaction |
| 3 | CRUD students | buat akun + `created_by` + validasi §7.1 + tabel search/filter §4.4 |
| 4 | CRUD evaluation_periods | aksi buka/tutup, tegakkan **periode tunggal** via `activate()` |
| 5 | CRUD course_class_assignments | team teaching, unik 4-kolom, validasi §7.1/§7.2 |
| 6 | Feature test + finalisasi TODO | uji semua aturan |

---

## Commit 1 — CRUD class_groups

```bash
php artisan make:controller Admin/ClassGroupController --no-interaction
php artisan make:request Admin/ClassGroupRequest --no-interaction
```

**`app/Http/Requests/Admin/ClassGroupRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClassGroupRequest extends FormRequest
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
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'year_level' => ['required', 'integer', 'between:1,4'],
            'class_letter' => ['required', 'string', 'size:1', 'alpha'],
            'capacity' => ['required', 'integer', 'between:1,60'],
        ];
    }

    /**
     * Aturan tambahan: year_level tidak melebihi jenjang prodi, dan
     * class_code ({KODE}{TAHUN}{HURUF}) unik per tahun ajaran (PRD §2.2).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $prodi = StudyProgram::find($this->input('study_program_id'));
                if (! $prodi) {
                    return;
                }

                $maxYear = intdiv($prodi->total_semesters, 2);
                if ((int) $this->input('year_level') > $maxYear) {
                    $validator->errors()->add('year_level', "Tahun maksimal prodi {$prodi->code} adalah {$maxYear}.");
                }

                $classCode = $prodi->code.$this->input('year_level').strtoupper((string) $this->input('class_letter'));

                $query = ClassGroup::where('academic_year', $this->input('academic_year'))
                    ->where('class_code', $classCode);

                if ($current = $this->route('class_group')) {
                    $query->whereKeyNot($current->getKey());
                }

                if ($query->exists()) {
                    $validator->errors()->add('class_letter', "Kelas {$classCode} sudah ada di tahun ajaran {$this->input('academic_year')}.");
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year.regex' => 'Format tahun ajaran: 2025/2026.',
        ];
    }
}
```

**`app/Http/Controllers/Admin/ClassGroupController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ClassGroupRequest;
use App\Models\ClassGroup;
use App\Models\StudyProgram;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ClassGroupController extends Controller
{
    public function index(): View
    {
        $classGroups = ClassGroup::with('studyProgram')
            ->orderBy('academic_year', 'desc')
            ->orderBy('class_code')
            ->paginate(20);

        return view('admin.class-groups.index', compact('classGroups'));
    }

    public function create(): View
    {
        return view('admin.class-groups.create', ['studyPrograms' => StudyProgram::orderBy('code')->get()]);
    }

    public function store(ClassGroupRequest $request): RedirectResponse
    {
        ClassGroup::create($this->withClassCode($request->validated()));

        return redirect()->route('admin.class-groups.index')->with('success', 'Kelas ditambahkan.');
    }

    public function edit(ClassGroup $classGroup): View
    {
        return view('admin.class-groups.edit', [
            'classGroup' => $classGroup,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
        ]);
    }

    public function update(ClassGroupRequest $request, ClassGroup $classGroup): RedirectResponse
    {
        $classGroup->update($this->withClassCode($request->validated()));

        return redirect()->route('admin.class-groups.index')->with('success', 'Kelas dibarui.');
    }

    public function destroy(ClassGroup $classGroup): RedirectResponse
    {
        $classGroup->delete();

        return redirect()->route('admin.class-groups.index')->with('success', 'Kelas dihapus.');
    }

    /**
     * Susun class_code = {KODE_PRODI}{TAHUN}{HURUF} (PRD §2.2).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withClassCode(array $data): array
    {
        $prodi = StudyProgram::findOrFail($data['study_program_id']);
        $data['class_letter'] = strtoupper($data['class_letter']);
        $data['class_code'] = $prodi->code.$data['year_level'].$data['class_letter'];

        return $data;
    }
}
```

**Route** (`routes/web.php`, grup admin + import):

```php
use App\Http\Controllers\Admin\ClassGroupController;

Route::resource('class-groups', ClassGroupController::class)->except('show');
```

**Views** — `resources/views/admin/class-groups/`.

`index.blade.php`:

```blade
<x-admin-layout header="Kelas">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $classGroups->total() }} kelas</p>
        <x-button :href="route('admin.class-groups.create')">Tambah Kelas</x-button>
    </div>

    @if ($classGroups->isEmpty())
        <x-empty-state message="Belum ada kelas. Tambahkan kelas per prodi per tahun ajaran." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kode Kelas</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Tahun Ajaran</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Tingkat</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kapasitas</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($classGroups as $class)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $class->class_code }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $class->studyProgram->code }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $class->academic_year }}</td>
                    <td class="px-4 py-3 text-muted">Tahun {{ $class->year_level }}</td>
                    <td class="px-4 py-3 text-muted">{{ $class->capacity }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.class-groups.edit', $class)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.class-groups.destroy', $class) }}"
                                onsubmit="return confirm('Hapus kelas {{ $class->class_code }}?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $classGroups->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($classGroup) && $classGroup->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.class-groups.update', $classGroup) : route('admin.class-groups.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="study_program_id" :value="'Program Studi'" />
            <x-select id="study_program_id" name="study_program_id" class="mt-1" required>
                <option value="">— pilih prodi —</option>
                @foreach ($studyPrograms as $prodi)
                    <option value="{{ $prodi->id }}"
                        @selected((int) old('study_program_id', $classGroup->study_program_id ?? 0) === $prodi->id)>
                        {{ $prodi->code }} — {{ $prodi->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('study_program_id')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="academic_year" :value="'Tahun Ajaran'" />
            <x-text-input id="academic_year" name="academic_year" class="mt-1 font-mono"
                :value="old('academic_year', $classGroup->academic_year ?? '2025/2026')" required />
            <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
        </div>

        <div class="grid grid-cols-3 gap-4">
            <div>
                <x-input-label for="year_level" :value="'Tingkat (tahun)'" />
                <x-text-input id="year_level" name="year_level" type="number" min="1" max="4" class="mt-1"
                    :value="old('year_level', $classGroup->year_level ?? 1)" required />
                <x-input-error :messages="$errors->get('year_level')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="class_letter" :value="'Huruf Kelas'" />
                <x-text-input id="class_letter" name="class_letter" maxlength="1" class="mt-1 font-mono uppercase"
                    :value="old('class_letter', $classGroup->class_letter ?? 'A')" required />
                <x-input-error :messages="$errors->get('class_letter')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="capacity" :value="'Kapasitas'" />
                <x-text-input id="capacity" name="capacity" type="number" min="1" max="60" class="mt-1"
                    :value="old('capacity', $classGroup->capacity ?? 25)" required />
                <x-input-error :messages="$errors->get('capacity')" class="mt-1" />
            </div>
        </div>

        <p class="text-xs text-muted">Kode kelas dibuat otomatis: <span class="font-mono">{KODE}{TINGKAT}{HURUF}</span> (mis. MI1A).</p>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Kelas' }}</x-button>
        <x-button variant="secondary" :href="route('admin.class-groups.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php` / `edit.blade.php`:

```blade
{{-- create.blade.php --}}
<x-admin-layout header="Tambah Kelas">
    <x-card class="max-w-xl">@include('admin.class-groups._form')</x-card>
</x-admin-layout>
```

```blade
{{-- edit.blade.php --}}
<x-admin-layout header="Edit Kelas">
    <x-card class="max-w-xl">@include('admin.class-groups._form')</x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/ClassGroupController.php app/Http/Requests/Admin/ClassGroupRequest.php routes/web.php resources/views/admin/class-groups/
git commit -m "Fase 5: CRUD class_groups

Auto-generate class_code {KODE}{TINGKAT}{HURUF} (PRD §2.2), unik per
tahun ajaran, year_level dibatasi jenjang prodi. Kolom kode pakai
font-mono.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — CRUD lecturers (buat akun user)

```bash
php artisan make:controller Admin/LecturerController --no-interaction
php artisan make:request Admin/LecturerRequest --no-interaction
```

**`app/Http/Requests/Admin/LecturerRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LecturerRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'nip' => ['required', 'string', 'max:255', Rule::unique('lecturers', 'nip')->ignore($this->route('lecturer'))],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('lecturer')?->user_id)],
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
        ];
    }
}
```

**`app/Http/Controllers/Admin/LecturerController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\LecturerRequest;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class LecturerController extends Controller
{
    public function index(): View
    {
        $lecturers = Lecturer::with(['user', 'studyProgram'])->orderBy('name')->paginate(20);

        return view('admin.lecturers.index', compact('lecturers'));
    }

    public function create(): View
    {
        return view('admin.lecturers.create', ['studyPrograms' => StudyProgram::orderBy('code')->get()]);
    }

    public function store(LecturerRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(config('evaluation.default_password')),
                'role' => Role::Lecturer,
                'must_change_password' => true,
            ]);

            $user->lecturer()->create([
                'name' => $data['name'],
                'nip' => $data['nip'],
                'study_program_id' => $data['study_program_id'],
            ]);
        });

        return redirect()->route('admin.lecturers.index')->with('success', 'Dosen ditambahkan (password default: password).');
    }

    public function edit(Lecturer $lecturer): View
    {
        return view('admin.lecturers.edit', [
            'lecturer' => $lecturer->load('user'),
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
        ]);
    }

    public function update(LecturerRequest $request, Lecturer $lecturer): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $lecturer): void {
            $lecturer->update([
                'name' => $data['name'],
                'nip' => $data['nip'],
                'study_program_id' => $data['study_program_id'],
            ]);

            $lecturer->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('admin.lecturers.index')->with('success', 'Dosen diperbarui.');
    }

    public function destroy(Lecturer $lecturer): RedirectResponse
    {
        DB::transaction(function () use ($lecturer): void {
            $user = $lecturer->user;
            $lecturer->delete();
            $user?->delete();
        });

        return redirect()->route('admin.lecturers.index')->with('success', 'Dosen dihapus.');
    }
}
```

> Password default diambil dari `config('evaluation.default_password')` (= `"password"`), `must_change_password=true` sehingga dosen wajib ganti saat login pertama (§8). Buat/ubah/hapus dibungkus transaction karena menyentuh 2 tabel (users + lecturers).

**Route** (grup admin + import):

```php
use App\Http\Controllers\Admin\LecturerController;

Route::resource('lecturers', LecturerController::class)->except('show');
```

**Views** — `resources/views/admin/lecturers/`.

`index.blade.php`:

```blade
<x-admin-layout header="Dosen">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $lecturers->total() }} dosen</p>
        <x-button :href="route('admin.lecturers.create')">Tambah Dosen</x-button>
    </div>

    @if ($lecturers->isEmpty())
        <x-empty-state message="Belum ada dosen. Tambahkan akun dosen (akan dibuat dengan password default)." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">NIP</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Email</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Prodi</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($lecturers as $lecturer)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $lecturer->nip }}</td>
                    <td class="px-4 py-3 text-ink">{{ $lecturer->name }}</td>
                    <td class="px-4 py-3 text-muted">{{ $lecturer->user->email }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $lecturer->studyProgram->code }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.lecturers.edit', $lecturer)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.lecturers.destroy', $lecturer) }}"
                                onsubmit="return confirm('Hapus dosen {{ $lecturer->name }} beserta akunnya?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $lecturers->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($lecturer) && $lecturer->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.lecturers.update', $lecturer) : route('admin.lecturers.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="name" :value="'Nama Dosen'" />
            <x-text-input id="name" name="name" class="mt-1"
                :value="old('name', $lecturer->name ?? '')" required />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="nip" :value="'NIP'" />
            <x-text-input id="nip" name="nip" class="mt-1 font-mono"
                :value="old('nip', $lecturer->nip ?? '')" required />
            <x-input-error :messages="$errors->get('nip')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" :value="'Email (untuk login)'" />
            <x-text-input id="email" name="email" type="email" class="mt-1"
                :value="old('email', $lecturer->user->email ?? '')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="study_program_id" :value="'Prodi Homebase'" />
            <x-select id="study_program_id" name="study_program_id" class="mt-1" required>
                <option value="">— pilih prodi —</option>
                @foreach ($studyPrograms as $prodi)
                    <option value="{{ $prodi->id }}"
                        @selected((int) old('study_program_id', $lecturer->study_program_id ?? 0) === $prodi->id)>
                        {{ $prodi->code }} — {{ $prodi->name }}
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('study_program_id')" class="mt-1" />
        </div>

        @unless ($editing)
            <p class="text-xs text-muted">Akun dibuat dengan password default <span class="font-mono">password</span>; dosen wajib menggantinya saat login pertama.</p>
        @endunless
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Dosen' }}</x-button>
        <x-button variant="secondary" :href="route('admin.lecturers.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php` / `edit.blade.php` (pola sama seperti sebelumnya):

```blade
{{-- create.blade.php --}}
<x-admin-layout header="Tambah Dosen">
    <x-card class="max-w-xl">@include('admin.lecturers._form')</x-card>
</x-admin-layout>
```

```blade
{{-- edit.blade.php --}}
<x-admin-layout header="Edit Dosen">
    <x-card class="max-w-xl">@include('admin.lecturers._form')</x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/LecturerController.php app/Http/Requests/Admin/LecturerRequest.php routes/web.php resources/views/admin/lecturers/
git commit -m "Fase 5: CRUD lecturers + buat akun user

Membuat user role=lecturer dengan password default & must_change_password
(§8) dalam transaction (users + lecturers). NIP & email unik.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — CRUD students (akun + created_by + §7.1 + tabel search/filter §4.4)

```bash
php artisan make:controller Admin/StudentController --no-interaction
php artisan make:request Admin/StudentRequest --no-interaction
```

**`app/Http/Requests/Admin/StudentRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\StudentStatus;
use App\Models\ClassGroup;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'nim' => ['required', 'string', 'max:255', Rule::unique('students', 'nim')->ignore($this->route('student'))],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('student')?->user_id)],
            'study_program_id' => ['required', Rule::exists('study_programs', 'id')],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')],
            'current_semester' => ['required', 'integer', 'between:1,8'],
            'status' => ['required', Rule::enum(StudentStatus::class)],
        ];
    }

    /**
     * §7.1: current_semester harus konsisten dengan year_level kelas
     * (tahun Y → semester 2Y-1 atau 2Y), dan prodi kelas cocok prodi mhs.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $class = ClassGroup::find($this->input('class_group_id'));
                if (! $class) {
                    return;
                }

                $sem = (int) $this->input('current_semester');
                $valid = [$class->year_level * 2 - 1, $class->year_level * 2];
                if (! in_array($sem, $valid, true)) {
                    $validator->errors()->add('current_semester', "Untuk kelas tahun {$class->year_level}, semester harus {$valid[0]} atau {$valid[1]}.");
                }

                if ((int) $this->input('study_program_id') !== $class->study_program_id) {
                    $validator->errors()->add('class_group_id', 'Kelas harus dari prodi yang sama dengan mahasiswa.');
                }
            },
        ];
    }
}
```

**`app/Http/Controllers/Admin/StudentController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Enums\StudentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StudentRequest;
use App\Models\ClassGroup;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $students = Student::query()
            ->with(['studyProgram', 'classGroup'])
            ->when($request->input('search'), function ($query, $search): void {
                $query->where(function ($q) use ($search): void {
                    $q->where('nim', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%");
                });
            })
            ->when($request->input('study_program_id'), fn ($q, $id) => $q->where('study_program_id', $id))
            ->when($request->input('class_group_id'), fn ($q, $id) => $q->where('class_group_id', $id))
            ->when($request->input('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderBy('nim')
            ->paginate(20)
            ->withQueryString();

        return view('admin.students.index', [
            'students' => $students,
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data): void {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(config('evaluation.default_password')),
                'role' => Role::Student,
                'must_change_password' => true,
            ]);

            $user->student()->create([
                'nim' => $data['nim'],
                'name' => $data['name'],
                'study_program_id' => $data['study_program_id'],
                'class_group_id' => $data['class_group_id'],
                'current_semester' => $data['current_semester'],
                'status' => $data['status'],
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa ditambahkan (password default: password).');
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', [
            'student' => $student->load('user'),
            'studyPrograms' => StudyProgram::orderBy('code')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'statuses' => StudentStatus::cases(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $student): void {
            $student->update([
                'nim' => $data['nim'],
                'name' => $data['name'],
                'study_program_id' => $data['study_program_id'],
                'class_group_id' => $data['class_group_id'],
                'current_semester' => $data['current_semester'],
                'status' => $data['status'],
            ]);

            $student->user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);
        });

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        DB::transaction(function () use ($student): void {
            $user = $student->user;
            $student->delete();
            $user?->delete();
        });

        return redirect()->route('admin.students.index')->with('success', 'Mahasiswa dihapus.');
    }
}
```

> `created_by = auth()->id()` (audit trail). `when()` membangun query search/filter tanpa if bertingkat (laravel-best-practices). `withQueryString()` mempertahankan filter saat pindah halaman.

**Route** (grup admin + import):

```php
use App\Http\Controllers\Admin\StudentController;

Route::resource('students', StudentController::class)->except('show');
```

**Views** — `resources/views/admin/students/`.

`index.blade.php` (search + filter chip GUIDELINE §4.4/§6.6):

```blade
<x-admin-layout header="Mahasiswa">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $students->total() }} mahasiswa</p>
        <x-button :href="route('admin.students.create')">Tambah Mahasiswa</x-button>
    </div>

    {{-- Filter bar (chip dropdown horizontal, GUIDELINE §6.6) --}}
    <form method="GET" class="mb-4 flex flex-wrap items-center gap-2">
        <x-text-input name="search" class="w-56" placeholder="Cari NIM / nama"
            :value="request('search')" />
        <x-select name="study_program_id" class="w-auto">
            <option value="">Semua Prodi</option>
            @foreach ($studyPrograms as $prodi)
                <option value="{{ $prodi->id }}" @selected(request('study_program_id') == $prodi->id)>{{ $prodi->code }}</option>
            @endforeach
        </x-select>
        <x-select name="class_group_id" class="w-auto">
            <option value="">Semua Kelas</option>
            @foreach ($classGroups as $class)
                <option value="{{ $class->id }}" @selected(request('class_group_id') == $class->id)>{{ $class->class_code }}</option>
            @endforeach
        </x-select>
        <x-select name="status" class="w-auto">
            <option value="">Semua Status</option>
            @foreach ($statuses as $status)
                <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ ucfirst($status->value) }}</option>
            @endforeach
        </x-select>
        <x-button type="submit" variant="secondary">Filter</x-button>
        @if (request()->hasAny(['search', 'study_program_id', 'class_group_id', 'status']))
            <x-button variant="secondary" :href="route('admin.students.index')">Reset</x-button>
        @endif
    </form>

    @if ($students->isEmpty())
        <x-empty-state message="Tidak ada mahasiswa yang cocok. Coba ubah filter atau tambahkan mahasiswa baru." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">NIM</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Sem</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($students as $student)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 font-mono text-ink">{{ $student->nim }}</td>
                    <td class="px-4 py-3 text-ink">{{ $student->name }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $student->classGroup->class_code }}</td>
                    <td class="px-4 py-3 text-muted">{{ $student->current_semester }}</td>
                    <td class="px-4 py-3"><x-badge-status :status="$student->status" /></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.students.edit', $student)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                onsubmit="return confirm('Hapus mahasiswa {{ $student->nim }} beserta akunnya?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $students->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($student) && $student->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.students.update', $student) : route('admin.students.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="nim" :value="'NIM'" />
                <x-text-input id="nim" name="nim" class="mt-1 font-mono"
                    :value="old('nim', $student->nim ?? '')" required />
                <x-input-error :messages="$errors->get('nim')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="name" :value="'Nama'" />
                <x-text-input id="name" name="name" class="mt-1"
                    :value="old('name', $student->name ?? '')" required />
                <x-input-error :messages="$errors->get('name')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="email" :value="'Email (untuk login)'" />
            <x-text-input id="email" name="email" type="email" class="mt-1"
                :value="old('email', $student->user->email ?? '')" required />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="study_program_id" :value="'Prodi'" />
                <x-select id="study_program_id" name="study_program_id" class="mt-1" required>
                    <option value="">— pilih prodi —</option>
                    @foreach ($studyPrograms as $prodi)
                        <option value="{{ $prodi->id }}"
                            @selected((int) old('study_program_id', $student->study_program_id ?? 0) === $prodi->id)>
                            {{ $prodi->code }} — {{ $prodi->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('study_program_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="class_group_id" :value="'Kelas'" />
                <x-select id="class_group_id" name="class_group_id" class="mt-1" required>
                    <option value="">— pilih kelas —</option>
                    @foreach ($classGroups as $class)
                        <option value="{{ $class->id }}"
                            @selected((int) old('class_group_id', $student->class_group_id ?? 0) === $class->id)>
                            {{ $class->class_code }} ({{ $class->academic_year }})
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('class_group_id')" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="current_semester" :value="'Semester Berjalan'" />
                <x-text-input id="current_semester" name="current_semester" type="number" min="1" max="8" class="mt-1"
                    :value="old('current_semester', $student->current_semester ?? '')" required />
                <x-input-error :messages="$errors->get('current_semester')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="status" :value="'Status'" />
                <x-select id="status" name="status" class="mt-1" required>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}"
                            @selected(old('status', ($student->status->value ?? 'aktif')) === $status->value)>
                            {{ ucfirst($status->value) }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('status')" class="mt-1" />
            </div>
        </div>

        @unless ($editing)
            <p class="text-xs text-muted">Akun dibuat dengan password default <span class="font-mono">password</span>; mahasiswa wajib menggantinya saat login pertama.</p>
        @endunless
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Mahasiswa' }}</x-button>
        <x-button variant="secondary" :href="route('admin.students.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php` / `edit.blade.php`:

```blade
{{-- create.blade.php --}}
<x-admin-layout header="Tambah Mahasiswa">
    <x-card class="max-w-2xl">@include('admin.students._form')</x-card>
</x-admin-layout>
```

```blade
{{-- edit.blade.php --}}
<x-admin-layout header="Edit Mahasiswa">
    <x-card class="max-w-2xl">@include('admin.students._form')</x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/StudentController.php app/Http/Requests/Admin/StudentRequest.php routes/web.php resources/views/admin/students/
git commit -m "Fase 5: CRUD students + akun + validasi §7.1 + tabel filter §4.4

Buat user role=student (password default, created_by admin) dalam
transaction; validasi konsistensi semester↔year_level & prodi kelas
(§7.1). Tabel dengan search NIM/nama + filter chip prodi/kelas/status
(GUIDELINE §4.4/§6.6), badge status.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — CRUD evaluation_periods (+ buka/tutup, periode tunggal)

```bash
php artisan make:controller Admin/EvaluationPeriodController --no-interaction
php artisan make:request Admin/EvaluationPeriodRequest --no-interaction
```

**`app/Http/Requests/Admin/EvaluationPeriodRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Enums\SemesterType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EvaluationPeriodRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'academic_year' => ['required', 'regex:/^\d{4}\/\d{4}$/'],
            'semester_type' => ['required', Rule::enum(SemesterType::class)],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ];
    }

    public function messages(): array
    {
        return [
            'academic_year.regex' => 'Format tahun ajaran: 2025/2026.',
            'end_date.after_or_equal' => 'Tanggal selesai tidak boleh sebelum tanggal mulai.',
        ];
    }
}
```

**`app/Http/Controllers/Admin/EvaluationPeriodController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PeriodStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\EvaluationPeriodRequest;
use App\Models\EvaluationPeriod;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EvaluationPeriodController extends Controller
{
    public function index(): View
    {
        $periods = EvaluationPeriod::orderBy('start_date', 'desc')->paginate(20);

        return view('admin.evaluation-periods.index', compact('periods'));
    }

    public function create(): View
    {
        return view('admin.evaluation-periods.create');
    }

    public function store(EvaluationPeriodRequest $request): RedirectResponse
    {
        // Periode baru selalu dibuat 'draft'; dibuka lewat aksi terpisah.
        EvaluationPeriod::create([...$request->validated(), 'status' => PeriodStatus::Draft]);

        return redirect()->route('admin.evaluation-periods.index')->with('success', 'Periode ditambahkan (status draft).');
    }

    public function edit(EvaluationPeriod $evaluationPeriod): View
    {
        return view('admin.evaluation-periods.edit', ['period' => $evaluationPeriod]);
    }

    public function update(EvaluationPeriodRequest $request, EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->update($request->validated());

        return redirect()->route('admin.evaluation-periods.index')->with('success', 'Periode diperbarui.');
    }

    public function destroy(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->delete();

        return redirect()->route('admin.evaluation-periods.index')->with('success', 'Periode dihapus.');
    }

    /**
     * Buka periode ini — otomatis menutup periode open lain (periode tunggal, §6.2/§7.7).
     */
    public function open(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->activate();

        return back()->with('success', "Periode \"{$evaluationPeriod->name}\" dibuka. Periode open lain otomatis ditutup.");
    }

    public function close(EvaluationPeriod $evaluationPeriod): RedirectResponse
    {
        $evaluationPeriod->update(['status' => PeriodStatus::Closed]);

        return back()->with('success', "Periode \"{$evaluationPeriod->name}\" ditutup.");
    }
}
```

> `open()` memanggil `EvaluationPeriod::activate()` (Fase 2) yang menutup periode `open` lain dalam 1 transaction — inilah penegakan **periode tunggal**. `[...$request->validated(), ...]` adalah spread array PHP 8.1 untuk menggabungkan status.

**Route** (grup admin + import) — resource + 2 aksi:

```php
use App\Http\Controllers\Admin\EvaluationPeriodController;

Route::resource('evaluation-periods', EvaluationPeriodController::class)->except('show');
Route::post('evaluation-periods/{evaluation_period}/open', [EvaluationPeriodController::class, 'open'])->name('evaluation-periods.open');
Route::post('evaluation-periods/{evaluation_period}/close', [EvaluationPeriodController::class, 'close'])->name('evaluation-periods.close');
```

**Views** — `resources/views/admin/evaluation-periods/`.

`index.blade.php`:

```blade
<x-admin-layout header="Periode Evaluasi">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $periods->total() }} periode</p>
        <x-button :href="route('admin.evaluation-periods.create')">Tambah Periode</x-button>
    </div>

    @if ($periods->isEmpty())
        <x-empty-state message="Belum ada periode evaluasi. Buat periode lalu buka saat evaluasi dimulai." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Nama</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Tahun Ajaran</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Rentang</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Status</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($periods as $period)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 text-ink">{{ $period->name }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $period->academic_year }}</td>
                    <td class="px-4 py-3 text-muted">{{ $period->start_date->format('d/m/Y') }} – {{ $period->end_date->format('d/m/Y') }}</td>
                    <td class="px-4 py-3"><x-badge-status :status="$period->status" /></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            @if ($period->status->value !== 'open')
                                <form method="POST" action="{{ route('admin.evaluation-periods.open', $period) }}"
                                    onsubmit="return confirm('Buka periode ini? Periode open lain akan ditutup.')">
                                    @csrf
                                    <x-button type="submit">Buka</x-button>
                                </form>
                            @else
                                <form method="POST" action="{{ route('admin.evaluation-periods.close', $period) }}">
                                    @csrf
                                    <x-button type="submit" variant="secondary">Tutup</x-button>
                                </form>
                            @endif
                            <x-button variant="secondary" :href="route('admin.evaluation-periods.edit', $period)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.evaluation-periods.destroy', $period) }}"
                                onsubmit="return confirm('Hapus periode ini?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $periods->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($period) && $period->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.evaluation-periods.update', $period) : route('admin.evaluation-periods.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="name" :value="'Nama Periode'" />
            <x-text-input id="name" name="name" class="mt-1"
                :value="old('name', $period->name ?? '')" required placeholder="mis. Ganjil 2025/2026" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="academic_year" :value="'Tahun Ajaran'" />
                <x-text-input id="academic_year" name="academic_year" class="mt-1 font-mono"
                    :value="old('academic_year', $period->academic_year ?? '2025/2026')" required />
                <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="semester_type" :value="'Jenis Semester'" />
                <x-select id="semester_type" name="semester_type" class="mt-1" required>
                    @foreach (\App\Enums\SemesterType::cases() as $type)
                        <option value="{{ $type->value }}"
                            @selected(old('semester_type', $period->semester_type->value ?? 'ganjil') === $type->value)>
                            {{ ucfirst($type->value) }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('semester_type')" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="start_date" :value="'Tanggal Mulai'" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1"
                    :value="old('start_date', optional($period->start_date ?? null)->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="end_date" :value="'Tanggal Selesai'" />
                <x-text-input id="end_date" name="end_date" type="date" class="mt-1"
                    :value="old('end_date', optional($period->end_date ?? null)->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Periode' }}</x-button>
        <x-button variant="secondary" :href="route('admin.evaluation-periods.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php` / `edit.blade.php`:

```blade
{{-- create.blade.php --}}
<x-admin-layout header="Tambah Periode Evaluasi">
    <x-card class="max-w-xl">@include('admin.evaluation-periods._form')</x-card>
</x-admin-layout>
```

```blade
{{-- edit.blade.php --}}
<x-admin-layout header="Edit Periode Evaluasi">
    <x-card class="max-w-xl">@include('admin.evaluation-periods._form')</x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/EvaluationPeriodController.php app/Http/Requests/Admin/EvaluationPeriodRequest.php routes/web.php resources/views/admin/evaluation-periods/
git commit -m "Fase 5: CRUD evaluation_periods + buka/tutup (periode tunggal)

CRUD periode (dibuat draft) + aksi Buka (activate() menutup periode
open lain, §6.2/§7.7) & Tutup. Badge status Open=teal/Closed=abu.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 5 — CRUD course_class_assignments (team teaching + §7.1/§7.2)

```bash
php artisan make:controller Admin/CourseClassAssignmentController --no-interaction
php artisan make:request Admin/CourseClassAssignmentRequest --no-interaction
```

**`app/Http/Requests/Admin/CourseClassAssignmentRequest.php`**:

```php
<?php

namespace App\Http\Requests\Admin;

use App\Models\ClassGroup;
use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CourseClassAssignmentRequest extends FormRequest
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
            'course_id' => ['required', Rule::exists('courses', 'id')],
            'class_group_id' => ['required', Rule::exists('class_groups', 'id')],
            'evaluation_period_id' => ['required', Rule::exists('evaluation_periods', 'id')],
            // Unik 4-kolom: dosen yg sama tak boleh diassign 2x ke MK+kelas+periode yg sama.
            'lecturer_id' => [
                'required',
                Rule::exists('lecturers', 'id'),
                Rule::unique('course_class_assignments', 'lecturer_id')
                    ->where('course_id', $this->input('course_id'))
                    ->where('class_group_id', $this->input('class_group_id'))
                    ->where('evaluation_period_id', $this->input('evaluation_period_id'))
                    ->ignore($this->route('course_class_assignment')),
            ],
        ];
    }

    /**
     * §7.1/§7.2: MK & kelas harus satu prodi, dan semester MK cocok tahun kelas.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $course = Course::find($this->input('course_id'));
                $class = ClassGroup::find($this->input('class_group_id'));
                if (! $course || ! $class) {
                    return;
                }

                if ($course->study_program_id !== $class->study_program_id) {
                    $validator->errors()->add('course_id', 'Mata kuliah dan kelas harus dari prodi yang sama.');

                    return;
                }

                $valid = [$class->year_level * 2 - 1, $class->year_level * 2];
                if (! in_array($course->semester, $valid, true)) {
                    $validator->errors()->add('course_id', "Semester MK ({$course->semester}) tidak sesuai tahun kelas ({$class->year_level}).");
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'lecturer_id.unique' => 'Dosen ini sudah diassign ke mata kuliah & kelas yang sama pada periode ini.',
        ];
    }
}
```

**`app/Http/Controllers/Admin/CourseClassAssignmentController.php`**:

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CourseClassAssignmentRequest;
use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CourseClassAssignmentController extends Controller
{
    public function index(): View
    {
        $assignments = CourseClassAssignment::with(['course', 'lecturer', 'classGroup', 'evaluationPeriod'])
            ->latest('id')
            ->paginate(20);

        return view('admin.course-class-assignments.index', compact('assignments'));
    }

    public function create(): View
    {
        return view('admin.course-class-assignments.create', $this->formData());
    }

    public function store(CourseClassAssignmentRequest $request): RedirectResponse
    {
        CourseClassAssignment::create([...$request->validated(), 'created_by' => auth()->id()]);

        return redirect()->route('admin.course-class-assignments.index')->with('success', 'Penugasan dosen ditambahkan.');
    }

    public function edit(CourseClassAssignment $courseClassAssignment): View
    {
        return view('admin.course-class-assignments.edit', [
            'assignment' => $courseClassAssignment,
            ...$this->formData(),
        ]);
    }

    public function update(CourseClassAssignmentRequest $request, CourseClassAssignment $courseClassAssignment): RedirectResponse
    {
        $courseClassAssignment->update($request->validated());

        return redirect()->route('admin.course-class-assignments.index')->with('success', 'Penugasan diperbarui.');
    }

    public function destroy(CourseClassAssignment $courseClassAssignment): RedirectResponse
    {
        $courseClassAssignment->delete();

        return redirect()->route('admin.course-class-assignments.index')->with('success', 'Penugasan dihapus.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'courses' => Course::with('studyProgram')->orderBy('code')->get(),
            'lecturers' => Lecturer::orderBy('name')->get(),
            'classGroups' => ClassGroup::orderBy('class_code')->get(),
            'periods' => EvaluationPeriod::orderBy('start_date', 'desc')->get(),
        ];
    }
}
```

> `created_by = auth()->id()`. Untuk **team teaching**, cukup buat penugasan lain dengan `lecturer_id` berbeda pada MK+kelas+periode yang sama — unique constraint 4-kolom mengizinkannya, dan hanya menolak dosen yang sama persis dua kali.

**Route** (grup admin + import):

```php
use App\Http\Controllers\Admin\CourseClassAssignmentController;

Route::resource('course-class-assignments', CourseClassAssignmentController::class)->except('show');
```

**Views** — `resources/views/admin/course-class-assignments/`.

`index.blade.php`:

```blade
<x-admin-layout header="Penugasan Dosen">
    <div class="mb-4 flex items-center justify-between">
        <p class="text-sm text-muted">{{ $assignments->total() }} penugasan</p>
        <x-button :href="route('admin.course-class-assignments.create')">Tambah Penugasan</x-button>
    </div>

    @if ($assignments->isEmpty())
        <x-empty-state message="Belum ada penugasan. Assign dosen ke mata kuliah + kelas untuk periode aktif." />
    @else
        <x-table>
            <x-slot name="head">
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Mata Kuliah</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Dosen</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Periode</th>
                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
            </x-slot>

            @foreach ($assignments as $a)
                <tr class="hover:bg-accent-soft">
                    <td class="px-4 py-3 text-ink">
                        <span class="font-mono text-muted">{{ $a->course->code }}</span> {{ $a->course->name }}
                    </td>
                    <td class="px-4 py-3 text-ink">{{ $a->lecturer->name }}</td>
                    <td class="px-4 py-3 font-mono text-muted">{{ $a->classGroup->class_code }}</td>
                    <td class="px-4 py-3 text-muted">{{ $a->evaluationPeriod->name }}</td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-2">
                            <x-button variant="secondary" :href="route('admin.course-class-assignments.edit', $a)">Edit</x-button>
                            <form method="POST" action="{{ route('admin.course-class-assignments.destroy', $a) }}"
                                onsubmit="return confirm('Hapus penugasan ini?')">
                                @csrf @method('DELETE')
                                <x-button variant="destructive">Hapus</x-button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-table>

        <div class="mt-4">{{ $assignments->links() }}</div>
    @endif
</x-admin-layout>
```

`_form.blade.php`:

```blade
@php $editing = isset($assignment) && $assignment->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.course-class-assignments.update', $assignment) : route('admin.course-class-assignments.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="course_id" :value="'Mata Kuliah'" />
            <x-select id="course_id" name="course_id" class="mt-1" required>
                <option value="">— pilih mata kuliah —</option>
                @foreach ($courses as $course)
                    <option value="{{ $course->id }}"
                        @selected((int) old('course_id', $assignment->course_id ?? 0) === $course->id)>
                        {{ $course->code }} — {{ $course->name }} ({{ $course->studyProgram->code }}, sem {{ $course->semester }})
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('course_id')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="lecturer_id" :value="'Dosen'" />
            <x-select id="lecturer_id" name="lecturer_id" class="mt-1" required>
                <option value="">— pilih dosen —</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}"
                        @selected((int) old('lecturer_id', $assignment->lecturer_id ?? 0) === $lecturer->id)>
                        {{ $lecturer->name }} ({{ $lecturer->nip }})
                    </option>
                @endforeach
            </x-select>
            <x-input-error :messages="$errors->get('lecturer_id')" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="class_group_id" :value="'Kelas'" />
                <x-select id="class_group_id" name="class_group_id" class="mt-1" required>
                    <option value="">— pilih kelas —</option>
                    @foreach ($classGroups as $class)
                        <option value="{{ $class->id }}"
                            @selected((int) old('class_group_id', $assignment->class_group_id ?? 0) === $class->id)>
                            {{ $class->class_code }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('class_group_id')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="evaluation_period_id" :value="'Periode'" />
                <x-select id="evaluation_period_id" name="evaluation_period_id" class="mt-1" required>
                    <option value="">— pilih periode —</option>
                    @foreach ($periods as $period)
                        <option value="{{ $period->id }}"
                            @selected((int) old('evaluation_period_id', $assignment->evaluation_period_id ?? 0) === $period->id)>
                            {{ $period->name }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('evaluation_period_id')" class="mt-1" />
            </div>
        </div>

        <p class="text-xs text-muted">Team teaching: untuk 2 dosen pada MK+kelas yang sama, buat penugasan terpisah dengan dosen berbeda.</p>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Penugasan' }}</x-button>
        <x-button variant="secondary" :href="route('admin.course-class-assignments.index')">Batal</x-button>
    </div>
</form>
```

`create.blade.php` / `edit.blade.php`:

```blade
{{-- create.blade.php --}}
<x-admin-layout header="Tambah Penugasan Dosen">
    <x-card class="max-w-xl">@include('admin.course-class-assignments._form')</x-card>
</x-admin-layout>
```

```blade
{{-- edit.blade.php --}}
<x-admin-layout header="Edit Penugasan Dosen">
    <x-card class="max-w-xl">@include('admin.course-class-assignments._form')</x-card>
</x-admin-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Admin/CourseClassAssignmentController.php app/Http/Requests/Admin/CourseClassAssignmentRequest.php routes/web.php resources/views/admin/course-class-assignments/
git commit -m "Fase 5: CRUD course_class_assignments (team teaching + §7.1/§7.2)

Penugasan dosen ke MK+kelas+periode, created_by admin. Unik 4-kolom
menolak dosen sama dobel tapi mengizinkan team teaching (dosen beda).
Validasi MK & kelas satu prodi + semester cocok tahun kelas.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 6 — Feature test + finalisasi TODO Fase 5

```bash
php artisan make:test Admin/ClassGroupCrudTest --pest --no-interaction
php artisan make:test Admin/AccountCreationTest --pest --no-interaction
php artisan make:test Admin/EvaluationPeriodTest --pest --no-interaction
php artisan make:test Admin/AssignmentTest --pest --no-interaction
```

**`tests/Feature/Admin/ClassGroupCrudTest.php`**:

```php
<?php

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use App\Models\User;

test('menambah kelas meng-generate class_code otomatis', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'degree_level' => 'D3', 'total_semesters' => 6]);

    $this->actingAs($admin)->post(route('admin.class-groups.store'), [
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => 1,
        'class_letter' => 'a',
        'capacity' => 25,
    ])->assertRedirect(route('admin.class-groups.index'));

    $this->assertDatabaseHas('class_groups', ['class_code' => 'MI1A', 'class_letter' => 'A']);
});

test('class_code tidak boleh dobel dalam satu tahun ajaran', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['code' => 'MI', 'total_semesters' => 6]);
    ClassGroup::factory()->create([
        'study_program_id' => $prodi->id, 'academic_year' => '2025/2026',
        'year_level' => 1, 'class_letter' => 'A', 'class_code' => 'MI1A',
    ]);

    $this->actingAs($admin)->post(route('admin.class-groups.store'), [
        'study_program_id' => $prodi->id,
        'academic_year' => '2025/2026',
        'year_level' => 1,
        'class_letter' => 'A',
        'capacity' => 25,
    ])->assertSessionHasErrors('class_letter');
});

test('year_level tidak boleh melebihi jenjang prodi', function () {
    $admin = User::factory()->admin()->create();
    $d3 = StudyProgram::factory()->create(['code' => 'MI', 'degree_level' => 'D3', 'total_semesters' => 6]);

    $this->actingAs($admin)->post(route('admin.class-groups.store'), [
        'study_program_id' => $d3->id,
        'academic_year' => '2025/2026',
        'year_level' => 4, // D3 maksimal tahun 3
        'class_letter' => 'A',
        'capacity' => 25,
    ])->assertSessionHasErrors('year_level');
});
```

**`tests/Feature/Admin/AccountCreationTest.php`**:

```php
<?php

use App\Models\ClassGroup;
use App\Models\StudyProgram;
use App\Models\User;

test('membuat dosen sekaligus akun user role=lecturer', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create();

    $this->actingAs($admin)->post(route('admin.lecturers.store'), [
        'name' => 'Budi Santoso',
        'nip' => '1990010112345',
        'email' => 'budi@siedu.test',
        'study_program_id' => $prodi->id,
    ])->assertRedirect(route('admin.lecturers.index'));

    $this->assertDatabaseHas('users', ['email' => 'budi@siedu.test', 'role' => 'lecturer', 'must_change_password' => true]);
    $this->assertDatabaseHas('lecturers', ['nip' => '1990010112345']);
});

test('membuat mahasiswa menolak semester tak sesuai tahun kelas (§7.1)', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]); // valid: sem 1/2

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'Ani', 'nim' => '2401001', 'email' => 'ani@siedu.test',
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id,
        'current_semester' => 3, // tidak valid untuk tahun 1
        'status' => 'aktif',
    ])->assertSessionHasErrors('current_semester');
});

test('membuat mahasiswa set created_by ke admin', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);

    $this->actingAs($admin)->post(route('admin.students.store'), [
        'name' => 'Ani', 'nim' => '2401002', 'email' => 'ani2@siedu.test',
        'study_program_id' => $prodi->id, 'class_group_id' => $class->id,
        'current_semester' => 1, 'status' => 'aktif',
    ])->assertRedirect(route('admin.students.index'));

    $this->assertDatabaseHas('students', ['nim' => '2401002', 'created_by' => $admin->id]);
});
```

**`tests/Feature/Admin/EvaluationPeriodTest.php`**:

```php
<?php

use App\Models\EvaluationPeriod;
use App\Models\User;

test('membuka periode menutup periode open lain (periode tunggal)', function () {
    $admin = User::factory()->admin()->create();
    $lama = EvaluationPeriod::factory()->open()->create();
    $baru = EvaluationPeriod::factory()->create(); // draft

    $this->actingAs($admin)->post(route('admin.evaluation-periods.open', $baru))
        ->assertRedirect();

    expect($baru->fresh()->status->value)->toBe('open');
    expect($lama->fresh()->status->value)->toBe('closed');
});
```

**`tests/Feature/Admin/AssignmentTest.php`**:

```php
<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use App\Models\StudyProgram;
use App\Models\User;

function assignmentPayload(): array
{
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]);
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 1]); // cocok tahun 1
    $period = EvaluationPeriod::factory()->open()->create();

    return compact('prodi', 'class', 'course', 'period');
}

test('team teaching: dua dosen boleh untuk MK+kelas yang sama', function () {
    $admin = User::factory()->admin()->create();
    ['class' => $class, 'course' => $course, 'period' => $period, 'prodi' => $prodi] = assignmentPayload();
    $dosenA = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $dosenB = Lecturer::factory()->create(['study_program_id' => $prodi->id]);

    $base = ['course_id' => $course->id, 'class_group_id' => $class->id, 'evaluation_period_id' => $period->id];

    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenA->id])
        ->assertRedirect();
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [...$base, 'lecturer_id' => $dosenB->id])
        ->assertRedirect();

    expect(CourseClassAssignment::count())->toBe(2);
});

test('dosen sama tak boleh diassign dobel (unik 4-kolom)', function () {
    $admin = User::factory()->admin()->create();
    ['class' => $class, 'course' => $course, 'period' => $period, 'prodi' => $prodi] = assignmentPayload();
    $dosen = Lecturer::factory()->create(['study_program_id' => $prodi->id]);
    $base = ['course_id' => $course->id, 'class_group_id' => $class->id, 'evaluation_period_id' => $period->id, 'lecturer_id' => $dosen->id];

    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), $base)->assertRedirect();
    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), $base)->assertSessionHasErrors('lecturer_id');
});

test('MK semester tak cocok tahun kelas ditolak (§7.1)', function () {
    $admin = User::factory()->admin()->create();
    $prodi = StudyProgram::factory()->create(['total_semesters' => 6]);
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id, 'year_level' => 1]); // sem 1/2
    $course = Course::factory()->create(['study_program_id' => $prodi->id, 'semester' => 3]); // tahun 2
    $period = EvaluationPeriod::factory()->open()->create();
    $dosen = Lecturer::factory()->create(['study_program_id' => $prodi->id]);

    $this->actingAs($admin)->post(route('admin.course-class-assignments.store'), [
        'course_id' => $course->id, 'class_group_id' => $class->id,
        'evaluation_period_id' => $period->id, 'lecturer_id' => $dosen->id,
    ])->assertSessionHasErrors('course_id');
});
```

Jalankan:

```bash
php artisan test --compact
```

Semua harus hijau. Terakhir, uji seluruh alur dengan data seed:

```bash
php artisan migrate:fresh --seed
```

lalu login `admin@siedu.test` / `password` dan cek tiap menu sidebar berfungsi.

**Update TODO.md** — centang **semua** item Fase 5 jadi `[x]`, update baris status:

> *Fase 0–5 selesai. Fase 5: 8 CRUD master data + komponen GUIDELINE + layout admin sidebar, dengan aturan jenjang §7.2, konsistensi semester §7.1, periode tunggal, team teaching. Feature test hijau. Siap lanjut Fase 6 (ClassPromotionService).*

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Admin/ TODO.md
git commit -m "Fase 5: feature test CRUD kompleks + finalisasi TODO

Uji auto class_code & unik, buat akun dosen/mahasiswa, §7.1 semester,
periode tunggal, team teaching & unik 4-kolom. Fase 5 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **Import lengkap** tiap file — `Rule`, `DB`, `Hash`, controller di `web.php`. (Penyebab error paling sering di fase-fase sebelumnya.)
- [ ] **`created_by = auth()->id()`** di StudentController & AssignmentController.
- [ ] **Transaction** saat buat/hapus dosen & mahasiswa (2 tabel: users + lecturers/students).
- [ ] **`->withQueryString()`** pada paginate index mahasiswa agar filter bertahan antar halaman.
- [ ] **Aksi buka/tutup periode** pakai `POST` (bukan GET) + `@csrf`.
- [ ] **Unik 4-kolom** assignment memakai `Rule::unique(...)->where(...)` tiga kali — jangan tertukar kolomnya.
- [ ] `vendor/bin/pint --dirty --format agent` sebelum tiap commit; `npm run build` bila menambah class Tailwind baru.

> Setelah Fase 5 kelar, file [PANDUAN_FASE_5.md](PANDUAN_FASE_5.md) & [PANDUAN_FASE_5B.md](PANDUAN_FASE_5B.md) boleh dihapus (opsional).
