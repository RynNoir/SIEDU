# Panduan Fase 8 — Modul Dosen (Dashboard Hasil + Kesan & Saran Anonim)

Panduan ngoding manual untuk Fase 8 di [TODO.md](TODO.md). Implementasi **PRD.md §6.4** (alur dosen), **§7.5** (threshold ≥5), **§7.6** (larang filter granular), **§8** (anonimitas). UI mengikuti **GUIDELINE.md §4.3** (wireframe dashboard dosen), **§5** (Rating Gauge mode display-only), §6.5 (kartu kesan & saran), §6.6 (filter chip), §6.7 (empty state threshold), §12 (copy netral).

Skill: **laravel-best-practices** (agregasi `AVG`+`groupBy`, `withCount` cegah N+1, authorization Policy/Gate) & **tailwindcss-development** (kartu, bar skor, filter chip) — diterapkan langsung.

## ⚠️ Aturan paling kritis fase ini: ANONIMITAS

**`student_id` TIDAK BOLEH pernah muncul** di query hasil, response, atau view yang diakses dosen (PRD §8). Semua query kesan & saran hanya mengambil **teks + rata-rata rating**, tidak pernah kolom identitas mahasiswa. Threshold ≥5 responden (§7.5) melindungi identitas saat responden sedikit.

## Inti fase ini (PRD §6.4)

1. Dashboard dosen: daftar MK/kelas yang **diampu dosen login** (`course_class_assignments.lecturer_id = dosen ini`).
2. Per assignment: **skor rata-rata per kategori** (agregasi `evaluation_answers.star_rating` di-group per `evaluation_questions.category`) + rata-rata keseluruhan + jumlah responden.
3. **Kesan & saran anonim** per assignment — hanya bila responden ≥ threshold; kartu dengan badge "Anonim" + gauge kecil + blok Kesan/Saran terpisah.
4. **Filter**: periode (daftar assignment), rentang rating (kesan & saran). **Tanpa filter granular** yang bisa mengidentifikasi individu (§7.6).
5. **Authorization**: dosen hanya lihat assignment miliknya (Policy).

## Keputusan desain (silakan koreksi bila tidak setuju)

1. **Dua halaman**: (a) *dashboard* = daftar assignment dosen + filter periode; (b) *detail assignment* = ringkasan + skor kategori + kesan & saran. Skor bersifat **per assignment/MK** (lebih bermakna daripada mencampur semua MK).
2. **Rating Gauge display-only** = bar horizontal proporsional (amber) untuk skor kategori + belah ketupat statis kecil untuk kartu kesan (§5) — bukan gauge interaktif.
3. **Authorization pakai Policy** (`CourseClassAssignmentPolicy@view`) dipanggil via `Gate::authorize()` (base `Controller` tidak punya trait `AuthorizesRequests`).
4. Query kesan & saran pakai `DB::table()` dengan **select eksplisit** (teks + avg rating) supaya mustahil tak sengaja membawa `student_id`.

---

## Peta Commit (4 commit)

| # | Commit | Isi |
|---|---|---|
| 1 | Layout dosen + komponen display + dashboard | `x-lecturer-layout`, `x-score-bar`, `x-rating-display`, daftar assignment + filter periode |
| 2 | Policy + detail assignment | agregasi skor per kategori + ringkasan (kartu §4.3) |
| 3 | Kesan & saran anonim | threshold §7.5, filter rating §6.6, empty state §6.7, badge "Anonim" |
| 4 | Feature test + TODO | agregasi benar, threshold, anonimitas, authorization |

---

## Commit 1 — Layout dosen + komponen display + dashboard (daftar assignment)

### `resources/views/components/lecturer-layout.blade.php`

```blade
@props(['header' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Dosen</title>

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
                <span class="ml-2 text-xs text-muted">Dosen</span>
            </div>
            <nav class="space-y-1 p-4">
                <a href="{{ route('lecturer.dashboard') }}"
                    class="block rounded-input px-3 py-2 text-sm {{ request()->routeIs('lecturer.dashboard') || request()->routeIs('lecturer.assignments.*') ? 'bg-accent-soft font-medium text-accent' : 'text-ink hover:bg-canvas' }}">
                    Hasil Evaluasi
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

### `resources/views/components/score-bar.blade.php` (§5 display-only)

```blade
@props(['label', 'score', 'max' => 5])

@php $pct = $max > 0 ? min(100, ($score / $max) * 100) : 0; @endphp

<div>
    <div class="flex items-center justify-between text-sm">
        <span class="text-ink">{{ $label }}</span>
        <span class="font-mono text-muted">{{ number_format((float) $score, 1) }} / {{ $max }}</span>
    </div>
    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-border">
        <div class="h-full rounded-full bg-rating" style="width: {{ $pct }}%"></div>
    </div>
</div>
```

### `resources/views/components/rating-display.blade.php` (§5 belah ketupat statis)

```blade
@props(['score', 'max' => 5])

@php $rounded = (int) round((float) $score); @endphp

<div class="flex items-center gap-1" aria-label="Rata-rata {{ number_format((float) $score, 1) }} dari {{ $max }}">
    @for ($i = 1; $i <= $max; $i++)
        <span class="text-base leading-none {{ $i <= $rounded ? 'text-rating' : 'text-border' }}">⬥</span>
    @endfor
    <span class="ml-1 font-mono text-xs text-muted">{{ number_format((float) $score, 1) }}</span>
</div>
```

### Controller — dashboard (daftar assignment dosen)

```bash
php artisan make:controller Lecturer/DashboardController --no-interaction
```

**`app/Http/Controllers/Lecturer/DashboardController.php`** (method `index` dulu):

```php
<?php

namespace App\Http\Controllers\Lecturer;

use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationPeriod;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $lecturer = auth()->user()->lecturer;
        $selectedPeriodId = $request->input('period_id');

        $assignments = CourseClassAssignment::query()
            ->with(['course', 'classGroup', 'evaluationPeriod'])
            ->where('lecturer_id', $lecturer->id)
            ->when($selectedPeriodId, fn ($q, $id) => $q->where('evaluation_period_id', $id))
            ->withCount('evaluations')
            ->orderByDesc('evaluation_period_id')
            ->get();

        return view('lecturer.dashboard', [
            'assignments' => $assignments,
            'periods' => EvaluationPeriod::orderByDesc('start_date')->get(),
            'selectedPeriodId' => $selectedPeriodId,
        ]);
    }
}
```

> `withCount('evaluations')` menghitung responden per assignment tanpa N+1 (laravel-best-practices §1).

**Route** (`routes/web.php`, grup lecturer + import) — **ganti** `Route::view('/dashboard', 'lecturer.dashboard')`:

```php
use App\Http\Controllers\Lecturer\DashboardController;

// SEBELUM: Route::view('/dashboard', 'lecturer.dashboard')->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
```

> Hapus baris `Route::view` lama — jangan biarkan dua route dengan nama sama (pelajaran dari Fase 7).

**View `resources/views/lecturer/dashboard.blade.php`**:

```blade
<x-lecturer-layout header="Hasil Evaluasi">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-muted">Mata kuliah & kelas yang Anda ampu</p>

        {{-- Filter chip periode (GUIDELINE §6.6) --}}
        <form method="GET" class="flex items-center gap-2">
            <x-select name="period_id" class="w-auto" onchange="this.form.submit()">
                <option value="">Semua Periode</option>
                @foreach ($periods as $period)
                    <option value="{{ $period->id }}" @selected((string) $selectedPeriodId === (string) $period->id)>{{ $period->name }}</option>
                @endforeach
            </x-select>
        </form>
    </div>

    @if ($assignments->isEmpty())
        <x-empty-state message="Belum ada mata kuliah yang diampu pada periode ini." />
    @else
        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($assignments as $assignment)
                <a href="{{ route('lecturer.assignments.show', $assignment) }}"
                    class="block rounded-card border border-border bg-surface p-4 transition hover:border-accent">
                    <p class="font-medium text-ink">{{ $assignment->course->name }}</p>
                    <p class="mt-0.5 text-sm text-muted">
                        <span class="font-mono">{{ $assignment->course->code }}</span> · Kelas
                        <span class="font-mono">{{ $assignment->classGroup->class_code }}</span>
                    </p>
                    <p class="mt-3 text-sm text-muted">{{ $assignment->evaluationPeriod->name }}</p>
                    <p class="mt-1 text-sm">
                        <span class="font-display text-lg font-semibold text-ink">{{ $assignment->evaluations_count }}</span>
                        <span class="text-muted">responden</span>
                    </p>
                </a>
            @endforeach
        </div>
    @endif
</x-lecturer-layout>
```

```bash
npm run build
git add resources/views/components/lecturer-layout.blade.php resources/views/components/score-bar.blade.php resources/views/components/rating-display.blade.php app/Http/Controllers/Lecturer/DashboardController.php routes/web.php resources/views/lecturer/dashboard.blade.php
git commit -m "Fase 8: layout dosen + komponen display + daftar assignment

x-lecturer-layout (sidebar), x-score-bar & x-rating-display (gauge
display-only §5). Dashboard mendaftar assignment dosen + jumlah
responden (withCount) + filter periode (§6.6).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — Policy + detail assignment (agregasi skor per kategori)

```bash
php artisan make:policy CourseClassAssignmentPolicy --model=CourseClassAssignment --no-interaction
```

**`app/Policies/CourseClassAssignmentPolicy.php`** — isi method `view` (hapus/biarkan yang lain):

```php
<?php

namespace App\Policies;

use App\Models\CourseClassAssignment;
use App\Models\User;

class CourseClassAssignmentPolicy
{
    /**
     * Dosen hanya boleh melihat assignment miliknya sendiri (PRD §6.4).
     */
    public function view(User $user, CourseClassAssignment $assignment): bool
    {
        return $user->isLecturer() && $assignment->lecturer_id === $user->lecturer?->id;
    }
}
```

> Policy auto-discovered (Laravel 11+): `CourseClassAssignment` → `CourseClassAssignmentPolicy`. Tidak perlu registrasi manual.

**Tambahkan method `show` ke `DashboardController`** (import baru: `EvaluationAnswer`, `Student`, `Gate`):

```php
// tambah use di atas:
use App\Models\EvaluationAnswer;
use App\Models\Student;
use Illuminate\Support\Facades\Gate;

    public function show(Request $request, CourseClassAssignment $assignment): View
    {
        Gate::authorize('view', $assignment);

        $assignment->load(['course', 'classGroup', 'evaluationPeriod']);

        $respondents = $assignment->evaluations()->count();
        $classSize = Student::where('class_group_id', $assignment->class_group_id)->count();

        // Rata-rata per kategori (agregasi answers join questions).
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

        return view('lecturer.assignments.show', [
            'assignment' => $assignment,
            'respondents' => $respondents,
            'classSize' => $classSize,
            'categoryScores' => $categoryScores,
            'overallAvg' => $overallAvg,
        ]);
    }
```

**Route** (grup lecturer):

```php
Route::get('assignments/{assignment}', [DashboardController::class, 'show'])->name('assignments.show');
```

**View `resources/views/lecturer/assignments/show.blade.php`** (wireframe §4.3 — bagian ringkasan & kategori; kesan & saran ditambah di Commit 3):

```blade
<x-lecturer-layout header="Detail Hasil Evaluasi">
    <a href="{{ route('lecturer.dashboard') }}" class="text-sm text-accent hover:underline">← Kembali</a>

    <div class="mt-3 mb-6">
        <h1 class="font-display text-2xl font-semibold">{{ $assignment->course->name }}</h1>
        <p class="mt-1 text-sm text-muted">
            <span class="font-mono">{{ $assignment->course->code }}</span> · Kelas
            <span class="font-mono">{{ $assignment->classGroup->class_code }}</span> · {{ $assignment->evaluationPeriod->name }}
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
</x-lecturer-layout>
```

```bash
vendor/bin/pint --dirty --format agent
git add app/Policies/CourseClassAssignmentPolicy.php app/Http/Controllers/Lecturer/DashboardController.php routes/web.php resources/views/lecturer/assignments/show.blade.php
git commit -m "Fase 8: detail assignment + agregasi skor per kategori + policy

Policy view (dosen hanya assignment miliknya, via Gate::authorize).
Detail: kartu ringkasan (rata-rata keseluruhan + responden/kelas) &
skor per kategori sebagai bar proporsional (§4.3/§5). Agregasi AVG
group per kategori.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — Kesan & saran anonim (threshold + filter rating + empty state)

**Tambahkan ke method `show`** (sebelum `return`), dan import `DB`:

```php
// tambah use di atas:
use Illuminate\Support\Facades\DB;

        // --- di dalam show(), setelah $overallAvg ---
        $threshold = (int) config('evaluation.anonymity_min_respondents');
        $ratingFilter = $request->input('rating'); // null | high | mid | low
        $impressions = collect();

        if ($respondents >= $threshold) {
            // PENTING: select eksplisit — TIDAK PERNAH student_id.
            $rows = DB::table('evaluation_impressions as i')
                ->join('evaluations as e', 'i.evaluation_id', '=', 'e.id')
                ->where('e.course_class_assignment_id', $assignment->id)
                ->where(fn ($q) => $q->whereNotNull('i.impression_text')->orWhereNotNull('i.suggestion_text'))
                ->selectRaw('i.impression_text, i.suggestion_text, (SELECT AVG(star_rating) FROM evaluation_answers a WHERE a.evaluation_id = e.id) as avg_rating')
                ->get();

            $impressions = collect($rows)->when($ratingFilter, function ($items) use ($ratingFilter) {
                return $items->filter(function ($r) use ($ratingFilter): bool {
                    $avg = (float) $r->avg_rating;

                    return match ($ratingFilter) {
                        'high' => $avg >= 4,
                        'mid' => $avg >= 3 && $avg < 4,
                        'low' => $avg < 3,
                        default => true,
                    };
                });
            })->values();
        }
```

Lalu **tambahkan ke array `view(...)`**: `'threshold' => $threshold`, `'impressions' => $impressions`, `'ratingFilter' => $ratingFilter`.

**Tambahkan ke `resources/views/lecturer/assignments/show.blade.php`** (setelah kartu skor per kategori, sebelum `</x-lecturer-layout>`):

```blade
    {{-- Kesan & Saran anonim (§6.5) --}}
    <x-card class="mt-4">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-muted">Kesan & Saran (Anonim)</h2>
                <p class="mt-0.5 text-xs text-muted">Ditampilkan tanpa identitas mahasiswa.</p>
            </div>

            @if ($respondents >= $threshold)
                <form method="GET" class="flex items-center gap-2">
                    <input type="hidden" name="period_id" value="{{ request('period_id') }}">
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

> Empty state memakai **persis** nada GUIDELINE §6.7 (jelaskan *kapan* akan tampil). Copy "Ditampilkan tanpa identitas mahasiswa" bernada netral (§12). Filter hanya per rentang rating — **tidak** granular per mahasiswa (§7.6).

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Lecturer/DashboardController.php resources/views/lecturer/assignments/show.blade.php
git commit -m "Fase 8: kesan & saran anonim + threshold + filter rating

Tampilkan kesan & saran hanya bila responden >= threshold (§7.5);
query select eksplisit tanpa student_id (§8); kartu badge Anonim +
gauge kecil (§6.5); filter rentang rating (§6.6); empty state §6.7.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — Feature test + finalisasi TODO

```bash
php artisan make:test Lecturer/DashboardTest --pest --no-interaction
```

**`tests/Feature/Lecturer/DashboardTest.php`**:

```php
<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationAnswer;
use App\Models\EvaluationImpression;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;

/**
 * Buat assignment + $count evaluasi (jawaban rating tetap + kesan/saran).
 */
function seedResults(CourseClassAssignment $assignment, int $count, int $rating = 4): void
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
            EvaluationAnswer::factory()->create([
                'evaluation_id' => $eval->id,
                'evaluation_question_id' => $q->id,
                'star_rating' => $rating,
            ]);
        }
        EvaluationImpression::factory()->create([
            'evaluation_id' => $eval->id,
            'impression_text' => "Kesan nomor {$i}",
            'suggestion_text' => 'Saran uji',
        ]);
    }
}

test('dosen melihat daftar assignment miliknya', function () {
    $assignment = CourseClassAssignment::factory()->create();

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.dashboard'))
        ->assertOk()
        ->assertSee($assignment->course->name);
});

test('skor rata-rata keseluruhan dihitung benar', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 5, rating: 4);

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('4.0'); // semua rating 4 → rata-rata 4.0
});

test('kesan & saran tersembunyi di bawah threshold', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 4); // < 5

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee('Kesan nomor 0')
        ->assertSee('minimal 5 mahasiswa');
});

test('kesan & saran tampil bila threshold terpenuhi', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 5);

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertSee('Kesan nomor 0')
        ->assertSee('Anonim');
});

test('identitas mahasiswa tidak pernah muncul di halaman hasil', function () {
    $assignment = CourseClassAssignment::factory()->create();
    seedResults($assignment, count: 5);
    $student = Student::where('class_group_id', $assignment->class_group_id)->first();

    $this->actingAs($assignment->lecturer->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertOk()
        ->assertDontSee($student->nim)
        ->assertDontSee($student->name);
});

test('dosen lain tidak bisa melihat assignment bukan miliknya', function () {
    $assignment = CourseClassAssignment::factory()->create();
    $lain = Lecturer::factory()->create();

    $this->actingAs($lain->user)->get(route('lecturer.assignments.show', $assignment))
        ->assertForbidden();
});
```

Jalankan:

```bash
php artisan test --compact --filter=DashboardTest
php artisan test --compact
```

Semua harus hijau.

**Update TODO.md** — centang semua item Fase 8, update status:

> *Fase 0–8 selesai. Fase 8: modul dosen — daftar assignment + agregasi skor per kategori (bar §5), kesan & saran anonim dengan threshold ≥5 (§7.5) tanpa membocorkan student_id (§8), filter periode/rating (§6.6), authorization Policy. Feature test hijau. Siap lanjut Fase 9 (Modul Kaprodi).*

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Lecturer/ TODO.md
git commit -m "Fase 8: feature test dashboard dosen + finalisasi TODO

Uji daftar assignment, agregasi skor, threshold anonimitas (sembunyi
<5, tampil >=5), student_id tak muncul, dosen lain ditolak (policy).
Fase 8 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **ANONIMITAS**: query kesan & saran pakai `DB::table()` dengan `selectRaw` eksplisit — **jangan pernah** ambil/`select *` yang membawa `student_id`. View tak pernah render identitas mahasiswa.
- [ ] **Import lengkap**: `EvaluationAnswer`, `Student`, `Gate`, `DB` di controller; `DashboardController` di `web.php`; hapus `Route::view` lecturer lama.
- [ ] **`Gate::authorize('view', $assignment)`** (bukan `$this->authorize`, karena base Controller tak punya trait).
- [ ] **Threshold** dari `config('evaluation.anonymity_min_respondents')` (default 5), bukan hardcode.
- [ ] **`withCount('evaluations')`** untuk responden per assignment (cegah N+1).
- [ ] Skor kategori pakai **bar proporsional `bg-rating`** (amber = data), bukan `bg-accent`.
- [ ] Filter hanya periode & rentang rating — **tidak** ada filter per-mahasiswa (§7.6).
- [ ] `vendor/bin/pint --dirty --format agent` sebelum tiap commit; `npm run build` bila ada class Tailwind baru.

> File panduan ini boleh dihapus setelah Fase 8 kelar (opsional): `git rm PANDUAN_FASE_8.md`.
