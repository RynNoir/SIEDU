# Panduan Fase 9 — Modul Kaprodi (Role Terpisah)

Panduan ngoding manual untuk Fase 9 di [TODO.md](TODO.md). Implementasi **PRD.md §6.5** (dashboard kaprodi, dibatasi satu prodi) + tetap patuh §7.5 (threshold), §7.6 (larang filter granular), §8 (anonimitas). UI **reuse komponen dashboard dosen Fase 8** (GUIDELINE §4.3, §5, §6.3, §6.5, §6.6, §6.7, §12).

Skill: **laravel-best-practices** (query scoped ke prodi, agregasi tanpa N+1, authorization Policy) & **tailwindcss-development** (reuse komponen, tabel perbandingan §6.3) — diterapkan.

## Inti fase ini (PRD §6.5)

- Kaprodi login → cakupan data **otomatis dibatasi** ke `study_program_id` pada akunnya (1 akun kaprodi = 1 prodi).
- Dashboard agregasi level prodi: filter **per dosen** & **per periode** (filter "per prodi" tak perlu — sudah otomatis).
- **Bandingkan skor antar dosen** yang mengampu MK sama di kelas paralel (adil, kurikulum paket).
- Tetap patuh anonimitas & threshold; **tanpa** filter granular per individu.
- **Authorization**: kaprodi tak bisa akses data prodi lain.

## Strategi reuse (penting)

TODO minta reuse komponen dashboard dosen. Supaya **query anonimitas hanya ada di satu tempat** (mengurangi risiko `student_id` bocor) dan UI konsisten, Fase 9 dimulai dengan **mengekstrak** logika hasil evaluasi Fase 8 ke:
- **`AssignmentResultService`** — agregasi 1 assignment (skor kategori, rata-rata, responden, kesan & saran anonim + threshold). Dipakai dosen **dan** kaprodi.
- **partial `partials/assignment-result.blade.php`** — markup ringkasan + kategori + kesan & saran. Di-`@include` oleh view dosen & kaprodi (masing-masing dibungkus layout-nya).

Fase 8 di-refactor memakai keduanya (perilaku tak berubah — test Fase 8 tetap hijau).

## Keputusan desain (silakan koreksi bila tidak setuju)

1. **Scope prodi via `whereRelation('classGroup', 'study_program_id', $prodiId)`** — assignment yang kelasnya di prodi kaprodi. (course & class selalu satu prodi, ditegakkan Fase 5.)
2. **Perbandingan = dashboard dikelompokkan per MK**: tiap MK menampilkan tabel semua dosen+kelas paralel yang mengampunya + rata-ratanya (§6.3).
3. **Policy `view` diperluas**: dosen→assignment miliknya, kaprodi→assignment di prodinya. Satu method melayani dua role.
4. Detail assignment kaprodi **identik** dengan dosen (reuse partial + service), termasuk threshold & anonimitas.

---

## Peta Commit (4 commit)

| # | Commit | Isi |
|---|---|---|
| 1 | Ekstrak service + partial (refactor Fase 8) | `AssignmentResultService`, `partials/assignment-result`, dosen pakai keduanya |
| 2 | Layout + dashboard kaprodi | `x-kaprodi-layout`, daftar per-MK (perbandingan) + filter dosen/periode |
| 3 | Detail kaprodi + policy | perluas policy untuk kaprodi + view detail (reuse partial) |
| 4 | Feature test + TODO | scope prodi, threshold, anonimitas, tolak prodi lain |

---

## Commit 1 — Ekstrak service + partial, refactor dosen

### `app/Services/AssignmentResultService.php`

```bash
php artisan make:class Services/AssignmentResultService --no-interaction
```

```php
<?php

namespace App\Services;

use App\Models\CourseClassAssignment;
use App\Models\EvaluationAnswer;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AssignmentResultService
{
    /**
     * Agregasi hasil evaluasi satu assignment. Anonim — TIDAK PERNAH student_id.
     *
     * @return array{respondents:int, classSize:int, categoryScores:Collection, overallAvg:float, threshold:int, impressions:Collection}
     */
    public function for(CourseClassAssignment $assignment, ?string $ratingFilter = null): array
    {
        $respondents = $assignment->evaluations()->count();
        $classSize = Student::where('class_group_id', $assignment->class_group_id)->count();

        $categoryScores = EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->join('evaluation_questions', 'evaluation_answers.evaluation_question_id', '=', 'evaluation_questions.id')
            ->where('evaluations.course_class_assignment_id', $assignment->id)
            ->groupBy('evaluation_questions.category')
            ->orderBy('evaluation_questions.category')
            ->selectRaw('evaluation_questions.category, AVG(evaluation_answers.star_rating) as avg_rating')
            ->get();

        $overallAvg = (float) EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->where('evaluations.course_class_assignment_id', $assignment->id)
            ->avg('evaluation_answers.star_rating');

        $threshold = (int) config('evaluation.anonymity_min_respondents');
        $impressions = collect();

        if ($respondents >= $threshold) {
            // Select eksplisit — tanpa student_id (PRD §8).
            $rows = DB::table('evaluation_impressions as i')
                ->join('evaluations as e', 'i.evaluation_id', '=', 'e.id')
                ->where('e.course_class_assignment_id', $assignment->id)
                ->where(fn ($q) => $q->whereNotNull('i.impression_text')->orWhereNotNull('i.suggestion_text'))
                ->selectRaw('i.impression_text, i.suggestion_text, (SELECT AVG(star_rating) FROM evaluation_answers a WHERE a.evaluation_id = e.id) as avg_rating')
                ->get();

            $impressions = collect($rows)->when($ratingFilter, fn ($items) => $items->filter(function ($r) use ($ratingFilter): bool {
                $avg = (float) $r->avg_rating;

                return match ($ratingFilter) {
                    'high' => $avg >= 4,
                    'mid' => $avg >= 3 && $avg < 4,
                    'low' => $avg < 3,
                    default => true,
                };
            }))->values();
        }

        return compact('respondents', 'classSize', 'categoryScores', 'overallAvg', 'threshold', 'impressions');
    }
}
```

### `resources/views/partials/assignment-result.blade.php` (baru)

Ini isi detail hasil (tanpa layout & back link). Variabel diterima dari controller: `$assignment, $respondents, $classSize, $categoryScores, $overallAvg, $threshold, $impressions, $ratingFilter`.

```blade
<div class="mt-3 mb-6">
    <h1 class="font-display text-2xl font-semibold">{{ $assignment->course->name }}</h1>
    <p class="mt-1 text-sm text-muted">
        <span class="font-mono">{{ $assignment->course->code }}</span> · Kelas
        <span class="font-mono">{{ $assignment->classGroup->class_code }}</span> ·
        {{ $assignment->lecturer->name }} · {{ $assignment->evaluationPeriod->name }}
    </p>
</div>

{{-- Kartu ringkasan (§4.3) --}}
<div class="grid gap-3 sm:grid-cols-2">
    <x-card>
        <p class="text-sm text-muted">Rata-rata Keseluruhan</p>
        <p class="mt-1 font-display text-4xl font-semibold text-ink">{{ number_format($overallAvg, 1) }}</p>
        <div class="mt-2"><x-rating-display :score="$overallAvg" /></div>
    </x-card>
    <x-card>
        <p class="text-sm text-muted">Responden</p>
        <p class="mt-1 font-display text-4xl font-semibold text-ink">{{ $respondents }}<span class="text-lg text-muted"> / {{ $classSize }}</span></p>
        <p class="mt-2 text-sm text-muted">mahasiswa mengisi evaluasi</p>
    </x-card>
</div>

{{-- Skor per kategori (bar proporsional §5) --}}
<x-card class="mt-4">
    <h2 class="mb-4 text-xs font-semibold uppercase tracking-wide text-muted">Skor Per Kategori</h2>
    @if ($categoryScores->isEmpty())
        <p class="text-sm text-muted">Belum ada data penilaian.</p>
    @else
        <div class="space-y-4">
            @foreach ($categoryScores as $row)
                <x-score-bar :label="$row->category" :score="$row->avg_rating" />
            @endforeach
        </div>
    @endif
</x-card>

{{-- Kesan & Saran anonim (§6.5) --}}
<x-card class="mt-4">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <div>
            <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Kesan & Saran (Anonim)</h2>
            <p class="mt-0.5 text-xs text-muted">Ditampilkan tanpa identitas mahasiswa.</p>
        </div>

        @if ($respondents >= $threshold)
            <form method="GET" class="flex items-center gap-2">
                {{-- Pertahankan filter lain (period_id/lecturer_id) apa pun konteksnya --}}
                @foreach (request()->except('rating', 'page') as $key => $val)
                    <input type="hidden" name="{{ $key }}" value="{{ $val }}">
                @endforeach
                <x-select name="rating" class="w-auto" onchange="this.form.submit()">
                    <option value="">Semua Rating</option>
                    <option value="high" @selected($ratingFilter === 'high')>Tinggi (≥ 4)</option>
                    <option value="mid" @selected($ratingFilter === 'mid')>Sedang (3–3.9)</option>
                    <option value="low" @selected($ratingFilter === 'low')>Rendah (&lt; 3)</option>
                </x-select>
            </form>
        @endif
    </div>

    @if ($respondents < $threshold)
        <x-empty-state message="Kesan & saran akan tampil setelah minimal {{ $threshold }} mahasiswa mengisi evaluasi untuk kelas ini." />
    @elseif ($impressions->isEmpty())
        <x-empty-state message="Tidak ada kesan & saran untuk filter ini." />
    @else
        <div class="space-y-3">
            @foreach ($impressions as $imp)
                <div class="rounded-card border border-border p-4">
                    <div class="mb-2 flex items-center justify-between gap-2">
                        <x-rating-display :score="$imp->avg_rating" />
                        <span class="rounded-full bg-muted/15 px-2 py-0.5 text-xs text-muted">Anonim</span>
                    </div>

                    @if ($imp->impression_text)
                        <div class="mt-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted">Kesan</p>
                            <p class="mt-0.5 text-sm text-ink">{{ $imp->impression_text }}</p>
                        </div>
                    @endif
                    @if ($imp->suggestion_text)
                        <div class="mt-2">
                            <p class="text-xs font-medium uppercase tracking-wide text-muted">Saran</p>
                            <p class="mt-0.5 text-sm text-ink">{{ $imp->suggestion_text }}</p>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</x-card>
```

### Refactor `app/Http/Controllers/Lecturer/DashboardController.php` — method `show`

Ganti **seluruh method `show`** jadi ramping (pakai service). Tambahkan `use App\Services\AssignmentResultService;` di atas:

```php
    public function show(Request $request, CourseClassAssignment $assignment, AssignmentResultService $results): View
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'classGroup', 'evaluationPeriod', 'lecturer']);

        return view('lecturer.assignments.show', [
            'assignment' => $assignment,
            'ratingFilter' => $request->input('rating'),
            ...$results->for($assignment, $request->input('rating')),
        ]);
    }
```

> Import `EvaluationAnswer`, `Student`, `DB` yang tak lagi dipakai akan dihapus otomatis oleh `pint` (rule `no_unused_imports`).

### Refactor `resources/views/lecturer/assignments/show.blade.php`

Ganti **seluruh isi** jadi ringkas (reuse partial):

```blade
<x-lecturer-layout header="Detail Hasil Evaluasi">
    <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali</a>

    @include('partials.assignment-result')
</x-lecturer-layout>
```

Verifikasi tidak ada regresi (test Fase 8 harus tetap hijau):

```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact --filter=DashboardTest
```

```bash
git add app/Services/AssignmentResultService.php resources/views/partials/assignment-result.blade.php app/Http/Controllers/Lecturer/DashboardController.php resources/views/lecturer/assignments/show.blade.php
git commit -m "Fase 9: ekstrak AssignmentResultService + partial hasil, refactor dosen

Pindahkan agregasi & query kesan-saran anonim ke service; markup detail
ke partial reusable. Dashboard dosen memakai keduanya (perilaku sama,
test Fase 8 hijau). Menyiapkan reuse untuk kaprodi.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — Layout + dashboard kaprodi (perbandingan per MK)

### `resources/views/components/kaprodi-layout.blade.php`

Sama pola `x-lecturer-layout`, ganti label & tampilkan prodi:

```blade
@props(['header' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Kaprodi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-body bg-canvas text-ink antialiased">
    <div x-data="{ sidebarOpen: false }" class="min-h-screen lg:flex">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false" class="fixed inset-0 z-20 bg-ink/40 lg:hidden"></div>

        <aside x-cloak :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
            class="fixed inset-y-0 left-0 z-30 w-64 transform border-r border-border bg-surface transition-transform lg:static lg:translate-x-0">
            <div class="flex h-16 items-center border-b border-border px-6">
                <span class="font-display text-lg font-semibold">SIEDU</span>
                <span class="ml-2 text-xs text-muted">Kaprodi {{ auth()->user()->studyProgram?->code }}</span>
            </div>
            <nav class="space-y-1 p-4">
                <a href="{{ route('kaprodi.dashboard') }}"
                    class="block rounded-input px-3 py-2 text-sm {{ request()->routeIs('kaprodi.*') ? 'bg-accent-soft font-medium text-accent' : 'text-ink hover:bg-canvas' }}">
                    Dashboard Prodi
                </a>
            </nav>
        </aside>

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
                    <button type="submit" class="text-sm text-muted hover:text-ink">{{ auth()->user()->name }} · Keluar</button>
                </form>
            </header>

            <main class="p-4 lg:p-6">{{ $slot }}</main>
        </div>
    </div>
</body>
</html>
```

### Controller kaprodi

```bash
php artisan make:controller Kaprodi/DashboardController --no-interaction
```

**`app/Http/Controllers/Kaprodi/DashboardController.php`** (method `index` dulu):

```php
<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationPeriod;
use App\Models\Lecturer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $prodiId = auth()->user()->study_program_id;
        $lecturerId = $request->input('lecturer_id');
        $periodId = $request->input('period_id');

        $assignments = CourseClassAssignment::query()
            ->with(['course', 'lecturer', 'classGroup', 'evaluationPeriod'])
            ->whereRelation('classGroup', 'study_program_id', $prodiId)
            ->when($lecturerId, fn ($q, $id) => $q->where('lecturer_id', $id))
            ->when($periodId, fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->withCount('evaluations')
            ->get();

        // Rata-rata per assignment dalam satu query (cegah N+1).
        $avgById = EvaluationAnswer::query()
            ->join('evaluations', 'evaluation_answers.evaluation_id', '=', 'evaluations.id')
            ->whereIn('evaluations.course_class_assignment_id', $assignments->pluck('id'))
            ->groupBy('evaluations.course_class_assignment_id')
            ->selectRaw('evaluations.course_class_assignment_id as assignment_id, AVG(evaluation_answers.star_rating) as avg_rating')
            ->pluck('avg_rating', 'assignment_id');

        return view('kaprodi.dashboard', [
            'byCourse' => $assignments->groupBy('course_id'),
            'avgById' => $avgById,
            'lecturers' => Lecturer::where('study_program_id', $prodiId)->orderBy('name')->get(),
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'lecturerId' => $lecturerId,
            'periodId' => $periodId,
        ]);
    }
}
```

**Route** (`routes/web.php`, grup kaprodi + import) — **ganti** `Route::view('/dashboard', 'kaprodi.dashboard')`:

```php
use App\Http\Controllers\Kaprodi\DashboardController as KaprodiDashboardController;

// SEBELUM: Route::view('/dashboard', 'kaprodi.dashboard')->name('dashboard');
Route::get('/dashboard', [KaprodiDashboardController::class, 'index'])->name('dashboard');
```

> Alias `KaprodiDashboardController` supaya tidak bentrok dengan `Lecturer\DashboardController` yang sudah di-import.

**View `resources/views/kaprodi/dashboard.blade.php`**:

```blade
<x-kaprodi-layout header="Dashboard Prodi {{ auth()->user()->studyProgram?->code }}">
    {{-- Filter: dosen & periode (§6.6). Filter prodi tak perlu — sudah otomatis. --}}
    <form method="GET" class="mb-6 flex flex-wrap items-center gap-2">
        <x-select name="lecturer_id" class="w-auto" onchange="this.form.submit()">
            <option value="">Semua Dosen</option>
            @foreach ($lecturers as $l)
                <option value="{{ $l->id }}" @selected((string) $lecturerId === (string) $l->id)>{{ $l->name }}</option>
            @endforeach
        </x-select>
        <x-select name="period_id" class="w-auto" onchange="this.form.submit()">
            <option value="">Semua Periode</option>
            @foreach ($periods as $p)
                <option value="{{ $p->id }}" @selected((string) $periodId === (string) $p->id)>{{ $p->name }}</option>
            @endforeach
        </x-select>
    </form>

    @if ($byCourse->isEmpty())
        <x-empty-state message="Belum ada data penugasan/evaluasi untuk prodi ini." />
    @else
        {{-- Perbandingan per MK: dosen & kelas paralel berdampingan --}}
        @foreach ($byCourse as $items)
            @php $course = $items->first()->course; @endphp
            <div class="mb-6">
                <h2 class="mb-2 font-medium text-ink">
                    <span class="font-mono text-muted">{{ $course->code }}</span> {{ $course->name }}
                </h2>

                <x-table>
                    <x-slot name="head">
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Dosen</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Kelas</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Periode</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Responden</th>
                        <th class="px-4 py-3 text-xs font-medium uppercase tracking-wide text-muted">Rata-rata</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wide text-muted">Aksi</th>
                    </x-slot>

                    @foreach ($items as $a)
                        @php $avg = $avgById->get($a->id); @endphp
                        <tr class="hover:bg-accent-soft">
                            <td class="px-4 py-3 text-ink">{{ $a->lecturer->name }}</td>
                            <td class="px-4 py-3 font-mono text-muted">{{ $a->classGroup->class_code }}</td>
                            <td class="px-4 py-3 text-muted">{{ $a->evaluationPeriod->name }}</td>
                            <td class="px-4 py-3 text-muted">{{ $a->evaluations_count }}</td>
                            <td class="px-4 py-3 font-mono text-ink">{{ $avg !== null ? number_format((float) $avg, 1) : '–' }}</td>
                            <td class="px-4 py-3 text-right">
                                <x-button variant="secondary" :href="route('kaprodi.assignments.show', $a)">Detail</x-button>
                            </td>
                        </tr>
                    @endforeach
                </x-table>
            </div>
        @endforeach
    @endif
</x-kaprodi-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add resources/views/components/kaprodi-layout.blade.php app/Http/Controllers/Kaprodi/DashboardController.php routes/web.php resources/views/kaprodi/dashboard.blade.php
git commit -m "Fase 9: layout & dashboard kaprodi (perbandingan per MK)

Dashboard dibatasi study_program_id kaprodi (whereRelation), dikelompokkan
per MK untuk membandingkan dosen di kelas paralel (§6.3). Rata-rata per
assignment dihitung 1 query (cegah N+1). Filter dosen & periode (§6.6).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — Detail kaprodi + perluas policy

### Perluas `app/Policies/CourseClassAssignmentPolicy.php`

Ganti method `view`:

```php
    /**
     * Dosen → assignment miliknya; Kaprodi → assignment di prodinya (PRD §6.4/§6.5).
     */
    public function view(User $user, CourseClassAssignment $assignment): bool
    {
        if ($user->isLecturer()) {
            return $assignment->lecturer_id === $user->lecturer?->id;
        }

        if ($user->isKaprodi()) {
            return $assignment->classGroup->study_program_id === $user->study_program_id;
        }

        return false;
    }
```

### Tambahkan `show` ke `Kaprodi/DashboardController`

Import: `use App\Services\AssignmentResultService;` dan `use Illuminate\Support\Facades\Gate;`.

```php
    public function show(Request $request, CourseClassAssignment $assignment, AssignmentResultService $results): View
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'lecturer', 'classGroup', 'evaluationPeriod']);

        return view('kaprodi.assignments.show', [
            'assignment' => $assignment,
            'ratingFilter' => $request->input('rating'),
            ...$results->for($assignment, $request->input('rating')),
        ]);
    }
```

**Route** (grup kaprodi):

```php
Route::get('assignments/{assignment}', [KaprodiDashboardController::class, 'show'])->name('assignments.show');
```

**View `resources/views/kaprodi/assignments/show.blade.php`** (reuse partial):

```blade
<x-kaprodi-layout header="Detail Hasil Evaluasi">
    <a href="{{ route('kaprodi.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali</a>

    @include('partials.assignment-result')
</x-kaprodi-layout>
```

Uji manual: `php artisan migrate:fresh --seed`, login `kaprodi.mi@siedu.test` / `password` (ganti password dulu bila diminta) → lihat dashboard prodi MI & detail.

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/CourseClassAssignmentPolicy.php app/Http/Controllers/Kaprodi/DashboardController.php routes/web.php resources/views/kaprodi/assignments/show.blade.php
git commit -m "Fase 9: detail kaprodi + perluas policy

Policy view kini melayani dosen (assignment sendiri) & kaprodi (prodi
sendiri). Detail assignment reuse partial+service (threshold & anonimitas
sama seperti dosen).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — Feature test + finalisasi TODO

```bash
php artisan make:test Kaprodi/DashboardTest --pest --no-interaction
```

**`tests/Feature/Kaprodi/DashboardTest.php`**:

```php
<?php

use App\Models\ClassGroup;
use App\Models\Course;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use App\Models\StudyProgram;
use App\Models\User;

/**
 * Assignment untuk MK & kelas di prodi tertentu.
 */
function assignmentInProdi(StudyProgram $prodi, ?string $courseName = null): CourseClassAssignment
{
    $class = ClassGroup::factory()->create(['study_program_id' => $prodi->id]);
    $course = Course::factory()->create([
        'study_program_id' => $prodi->id,
        'name' => $courseName ?? fake()->unique()->words(3, true),
    ]);

    return CourseClassAssignment::factory()->create([
        'class_group_id' => $class->id,
        'course_id' => $course->id,
    ]);
}

function seedKaprodiResults(CourseClassAssignment $assignment, int $count): void
{
    $questions = EvaluationQuestion::factory()->count(2)->create(['is_active' => true]);
    for ($i = 0; $i < $count; $i++) {
        $student = Student::factory()->create(['class_group_id' => $assignment->class_group_id]);
        $eval = Evaluation::factory()->create([
            'student_id' => $student->id,
            'course_class_assignment_id' => $assignment->id,
            'evaluation_period_id' => $assignment->evaluation_period_id,
        ]);
        foreach ($questions as $q) {
            EvaluationAnswer::factory()->create(['evaluation_id' => $eval->id, 'evaluation_question_id' => $q->id, 'star_rating' => 4]);
        }
        EvaluationImpression::factory()->create(['evaluation_id' => $eval->id, 'impression_text' => "Kesan {$i}", 'suggestion_text' => 'Saran']);
    }
}

test('kaprodi hanya melihat data prodinya di dashboard', function () {
    $prodiA = StudyProgram::factory()->create();
    $prodiB = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodiA->id]);

    $a = assignmentInProdi($prodiA, 'Basis Data A');
    $b = assignmentInProdi($prodiB, 'Jaringan B');

    $this->actingAs($kaprodi)->get(route('kaprodi.dashboard'))
        ->assertOk()
        ->assertSee('Basis Data A')
        ->assertDontSee('Jaringan B');
});

test('kaprodi tidak bisa melihat detail assignment prodi lain', function () {
    $prodiA = StudyProgram::factory()->create();
    $prodiB = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodiA->id]);

    $assignmentB = assignmentInProdi($prodiB);

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignmentB))
        ->assertForbidden();
});

test('detail kaprodi patuh threshold & anonimitas', function () {
    $prodi = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodi->id]);
    $assignment = assignmentInProdi($prodi);
    seedKaprodiResults($assignment, count: 5);

    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('Kesan 0')
        ->assertSee('Anonim')
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('kesan & saran tersembunyi di bawah threshold untuk kaprodi', function () {
    $prodi = StudyProgram::factory()->create();
    $kaprodi = User::factory()->kaprodi()->create(['study_program_id' => $prodi->id]);
    $assignment = assignmentInProdi($prodi);
    seedKaprodiResults($assignment, count: 4);

    $this->actingAs($kaprodi)->get(route('kaprodi.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee('Kesan 0')
        ->assertSee('minimal 5 mahasiswa');
});
```

Jalankan:

```bash
php artisan test --compact --filter=Kaprodi
php artisan test --compact
```

Semua harus hijau (termasuk Fase 8 yang di-refactor).

**Update TODO.md** — centang semua item Fase 9, update status:

> *Fase 0–9 selesai. Fase 9: modul kaprodi — dashboard agregasi dibatasi study_program_id, perbandingan skor antar dosen per MK (§6.3), reuse service+partial hasil evaluasi dari dosen (threshold & anonimitas tetap), authorization policy prodi. Feature test hijau. Siap lanjut Fase 10 (Polish & E2E).*

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Kaprodi/ TODO.md
git commit -m "Fase 9: feature test dashboard kaprodi + finalisasi TODO

Uji scope prodi (lihat prodi sendiri, tolak prodi lain via policy),
threshold & anonimitas di detail kaprodi. Fase 9 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **Alias import** `Kaprodi\DashboardController as KaprodiDashboardController` di `web.php` (nama sama dengan controller dosen).
- [ ] **Hapus `Route::view` kaprodi lama** — jangan biarkan dua route `kaprodi.dashboard` (pelajaran Fase 7).
- [ ] **Refactor Fase 8**: jalankan `DashboardTest` (dosen) setelah refactor untuk memastikan tak ada regresi.
- [ ] **Policy** menangani dua role — pastikan cabang `isKaprodi()` mengecek `classGroup->study_program_id`.
- [ ] Scope pakai **`whereRelation('classGroup', 'study_program_id', $prodiId)`** — bukan filter di PHP.
- [ ] Anonimitas & threshold datang dari **service yang sama** dengan dosen — tidak menduplikasi query.
- [ ] `vendor/bin/pint --dirty --format agent` (juga menghapus import tak terpakai di controller dosen) sebelum commit.

> Setelah Fase 9 kelar, file panduan Fase 8 & 9 boleh dihapus (opsional).
