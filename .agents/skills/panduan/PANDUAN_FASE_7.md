# Panduan Fase 7 — Modul Mahasiswa (Pengisian Evaluasi)

Panduan ngoding manual untuk Fase 7 di [TODO.md](TODO.md). Implementasi **PRD.md §6.3** (alur mahasiswa) & aturan §7.4 (anti-submit-ganda). UI wajib mengikuti **GUIDELINE.md §4.2** (wireframe form evaluasi), **§5** (Rating Gauge — elemen signature), §6.5 (kesan & saran), §8 (motion), §9 (aksesibilitas), §10 (responsif mobile), §12 (copy).

Skill: **tailwindcss-development** (Rating Gauge interaktif + responsif) & **laravel-best-practices** (controller, FormRequest, transaction, guard) — diterapkan langsung.

## Inti fase ini (PRD §6.3)

1. Mahasiswa login → lihat **daftar evaluasi** yang harus diisi: dari `course_class_assignments` di mana `class_group_id` = kelasnya, pada periode `open`.
2. Tiap baris assignment = **satu kartu/form terpisah** — termasuk **team teaching** (1 MK 2 dosen = 2 form, label "MK — Dosen").
3. Isi kuesioner (semua pertanyaan aktif, rating 1–5 **wajib**) + kesan & saran (opsional).
4. Submit → buat `evaluation` + `evaluation_answers` + `evaluation_impression` dalam 1 transaction. **Cegah submit ganda**.
5. **Guard**: hanya boleh mengisi assignment kelasnya sendiri di periode `open`.

## Keputusan desain (silakan koreksi bila tidak setuju)

1. **Rating Gauge** = 5 belah ketupat `⬥` (BUKAN bintang), interaktif via Alpine, kosong `border` → terisi `rating` (amber), transisi 150ms (§5/§8), target sentuh 44px & keyboard-accessible (§9/§10). Skor numerik `font-mono` "4 / 5" di samping.
2. **Layout mahasiswa terpisah** (`x-student-layout`) — top bar + konten 1 kolom + **bottom-nav mobile** (§10), karena mahasiswa banyak akses via HP.
3. **Landing mahasiswa = daftar evaluasi**: route `student.dashboard` dijadikan redirect ke `student.evaluations.index` (tidak mengubah `dashboardRoute()` / test Fase 4).
4. **"Semua wajib dinilai"** ditegakkan di FormRequest dengan 1 pesan ramah (§12), bukan error per-pertanyaan yang berantakan.
5. Assignment yang **sudah** diisi ditandai & tak bisa dibuka lagi (redirect + info) — konsisten dengan unique constraint DB.

---

## Peta Commit (4 commit)

| # | Commit | Isi |
|---|---|---|
| 1 | Rating Gauge + layout mahasiswa | `x-rating-gauge` (§5) + `x-student-layout` (§10) + redirect landing |
| 2 | Daftar evaluasi | controller `index` + route + view (done/undone, team teaching) |
| 3 | Form + submit | `show` + `store` + FormRequest + view (wireframe §4.2) + guard + anti-ganda |
| 4 | Feature test + TODO | uji submit, anti-ganda, team teaching, akses lintas-kelas |

---

## Commit 1 — Rating Gauge + layout mahasiswa

### `resources/views/components/rating-gauge.blade.php` (GUIDELINE §5)

```blade
@props(['name', 'value' => 0, 'max' => 5])

{{-- Gauge interaktif: 5 belah ketupat ⬥ (bukan bintang). Kosong=border, terisi=rating (amber).
     Keyboard: Tab antar notch, Enter/Space memilih. Target sentuh 44px (size-11). --}}
<div x-data="{ rating: {{ (int) $value }}, hover: 0 }" class="flex items-center gap-3">
    <input type="hidden" name="{{ $name }}" :value="rating">

    <div class="flex items-center gap-1" role="radiogroup" aria-label="Nilai 1 sampai {{ $max }}">
        @for ($i = 1; $i <= $max; $i++)
            <button type="button"
                role="radio"
                :aria-checked="rating === {{ $i }}"
                aria-label="Nilai {{ $i }}"
                @click="rating = {{ $i }}"
                @mouseenter="hover = {{ $i }}"
                @mouseleave="hover = 0"
                @keydown.enter.prevent="rating = {{ $i }}"
                @keydown.space.prevent="rating = {{ $i }}"
                class="flex size-11 items-center justify-center rounded-input transition duration-150 focus:outline-none focus:ring-2 focus:ring-accent">
                <span class="text-2xl leading-none transition-colors duration-150"
                    :class="(hover || rating) >= {{ $i }} ? 'text-rating' : 'text-border'">⬥</span>
            </button>
        @endfor
    </div>

    <span class="font-mono text-sm text-muted"
        x-text="rating ? rating + ' / {{ $max }}' : '– / {{ $max }}'"></span>
</div>
```

### `resources/views/components/student-layout.blade.php` (GUIDELINE §10)

```blade
@props(['header' => null])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIEDU') }} — Evaluasi</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@500;600&family=IBM+Plex+Sans:wght@400;500;600&family=IBM+Plex+Mono:wght@500&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col bg-canvas font-body text-ink antialiased">
    <header class="flex h-16 items-center justify-between border-b border-border bg-surface px-4 lg:px-8">
        <a href="{{ route('student.evaluations.index') }}" class="font-display text-lg font-semibold">SIEDU</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm text-muted hover:text-ink">{{ auth()->user()->name }} · Keluar</button>
        </form>
    </header>

    <main class="mx-auto w-full max-w-3xl flex-1 px-4 py-6 pb-24 lg:pb-8">
        @if ($header)
            <h1 class="mb-4 font-display text-2xl font-semibold">{{ $header }}</h1>
        @endif

        @if (session('success'))
            <div class="mb-4 rounded-card border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">
                {{ session('success') }}
            </div>
        @endif

        {{ $slot }}
    </main>

    {{-- Bottom-nav mobile (§10) --}}
    <nav class="fixed inset-x-0 bottom-0 z-20 flex border-t border-border bg-surface lg:hidden">
        <a href="{{ route('student.evaluations.index') }}"
            class="flex flex-1 flex-col items-center gap-1 py-2 text-xs {{ request()->routeIs('student.evaluations.*') ? 'text-accent' : 'text-muted' }}">
            <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
            </svg>
            Evaluasi
        </a>
        <form method="POST" action="{{ route('logout') }}" class="flex-1">
            @csrf
            <button type="submit" class="flex w-full flex-col items-center gap-1 py-2 text-xs text-muted">
                <svg class="size-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
                Keluar
            </button>
        </form>
    </nav>
</body>
</html>
```

### Ubah landing mahasiswa → daftar evaluasi

Di `routes/web.php`, **ganti** baris placeholder dashboard mahasiswa:

```php
// SEBELUM: Route::view('/dashboard', 'student.dashboard')->name('dashboard');
Route::get('/dashboard', fn () => redirect()->route('student.evaluations.index'))->name('dashboard');
```

> `dashboardRoute()` tetap mengembalikan `student.dashboard`, jadi test Fase 4 tak berubah — route ini sekarang meneruskan ke daftar evaluasi. (Boleh hapus `resources/views/student/dashboard.blade.php` yang tak terpakai.)

```bash
npm run build   # ada class Tailwind baru (gauge, layout)
git add resources/views/components/rating-gauge.blade.php resources/views/components/student-layout.blade.php routes/web.php
git commit -m "Fase 7: komponen Rating Gauge + layout mahasiswa

x-rating-gauge (5 belah ketupat ⬥, interaktif Alpine, amber saat
terisi, keyboard-accessible, target sentuh 44px — GUIDELINE §5/§8/§9).
x-student-layout dengan bottom-nav mobile (§10). Landing mahasiswa
diarahkan ke daftar evaluasi.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 2 — Daftar evaluasi

```bash
php artisan make:controller Student/EvaluationController --no-interaction
```

**`app/Http/Controllers/Student/EvaluationController.php`** (method `index` dulu):

```php
<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use Illuminate\View\View;

class EvaluationController extends Controller
{
    public function index(): View
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->first();

        $assignments = collect();
        $doneIds = [];

        if ($period && $student) {
            $assignments = CourseClassAssignment::with(['course', 'lecturer'])
                ->where('class_group_id', $student->class_group_id)
                ->where('evaluation_period_id', $period->id)
                ->get();

            $doneIds = Evaluation::where('student_id', $student->id)
                ->where('evaluation_period_id', $period->id)
                ->pluck('course_class_assignment_id')
                ->all();
        }

        return view('student.evaluations.index', compact('period', 'assignments', 'doneIds'));
    }
}
```

**Route** (`routes/web.php`, grup student + import):

```php
use App\Http\Controllers\Student\EvaluationController;

// di dalam grup student:
Route::get('evaluations', [EvaluationController::class, 'index'])->name('evaluations.index');
```

**View `resources/views/student/evaluations/index.blade.php`**:

```blade
<x-student-layout header="Daftar Evaluasi">
    @if (! $period)
        <x-empty-state message="Belum ada periode evaluasi yang dibuka. Silakan kembali saat periode evaluasi aktif." />
    @else
        <p class="mb-4 text-sm text-muted">
            Periode: <span class="font-medium text-ink">{{ $period->name }}</span>
        </p>

        @if ($assignments->isEmpty())
            <x-empty-state message="Tidak ada mata kuliah untuk dievaluasi di kelas Anda pada periode ini." />
        @else
            <div class="space-y-3">
                @foreach ($assignments as $assignment)
                    @php $done = in_array($assignment->id, $doneIds, true); @endphp
                    <div class="flex items-center justify-between gap-4 rounded-card border border-border bg-surface p-4">
                        <div class="min-w-0">
                            {{-- Team teaching: label jelas "MK — Dosen" (PRD §6.3.4) --}}
                            <p class="truncate font-medium text-ink">{{ $assignment->course->name }}</p>
                            <p class="truncate text-sm text-muted">
                                <span class="font-mono">{{ $assignment->course->code }}</span> · {{ $assignment->lecturer->name }}
                            </p>
                        </div>

                        @if ($done)
                            <span class="inline-flex shrink-0 items-center gap-1.5 rounded-full bg-success/15 px-2.5 py-0.5 text-xs font-medium text-success">
                                <span class="size-1.5 rounded-full bg-current"></span> Sudah diisi
                            </span>
                        @else
                            <x-button class="shrink-0" :href="route('student.evaluations.show', $assignment)">Isi Evaluasi</x-button>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    @endif
</x-student-layout>
```

> Team teaching muncul alami sebagai **beberapa kartu terpisah** (satu per baris assignment) dengan label "MK — Dosen".

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Student/EvaluationController.php routes/web.php resources/views/student/evaluations/index.blade.php
git commit -m "Fase 7: daftar evaluasi mahasiswa

Query assignment kelas mahasiswa pada periode open, tandai sudah/belum
diisi (join evaluations). Team teaching tampil sebagai kartu terpisah
berlabel MK — Dosen (PRD §6.3).

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 3 — Form evaluasi + submit (wireframe §4.2, anti-ganda, guard)

```bash
php artisan make:request Student/EvaluationRequest --no-interaction
```

**`app/Http/Requests/Student/EvaluationRequest.php`**:

```php
<?php

namespace App\Http\Requests\Student;

use App\Models\EvaluationQuestion;
use Illuminate\Foundation\Http\FormRequest;

class EvaluationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // guard kelas/periode dilakukan di controller
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answers' => ['required', 'array'],
            'answers.*' => ['integer', 'between:0,5'],
            'impression_text' => ['nullable', 'string', 'max:2000'],
            'suggestion_text' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Pastikan SEMUA pertanyaan aktif diberi nilai 1–5 (§6.3, copy §12).
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function ($validator): void {
                $activeIds = EvaluationQuestion::active()->pluck('id')->all();
                $answers = (array) $this->input('answers', []);

                $allAnswered = collect($activeIds)->every(function ($id) use ($answers): bool {
                    $value = (int) ($answers[$id] ?? 0);

                    return $value >= 1 && $value <= 5;
                });

                if (! $allAnswered) {
                    $validator->errors()->add('answers', 'Semua pertanyaan wajib diberi nilai sebelum mengirim.');
                }
            },
        ];
    }
}
```

**Tambahkan method `show` & `store` + guard ke `EvaluationController`** (import baru: `EvaluationRequest`, `Student`, `CourseClassAssignment`, `EvaluationQuestion`, `DB`, `RedirectResponse`):

```php
// tambahkan use di atas:
use App\Http\Requests\Student\EvaluationRequest;
use App\Models\CourseClassAssignment;
use App\Models\EvaluationQuestion;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

    public function show(CourseClassAssignment $assignment): View|RedirectResponse
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->first();
        abort_if($period === null, 403);
        $this->guardAssignment($assignment, $student, $period->id);

        if ($this->alreadySubmitted($student, $assignment, $period->id)) {
            return redirect()->route('student.evaluations.index')->with('success', 'Evaluasi ini sudah pernah diisi.');
        }

        $assignment->load(['course', 'lecturer']);
        $questions = EvaluationQuestion::active()->get();

        return view('student.evaluations.show', compact('assignment', 'questions'));
    }

    public function store(EvaluationRequest $request, CourseClassAssignment $assignment): RedirectResponse
    {
        $student = auth()->user()->student;
        $period = EvaluationPeriod::open()->firstOrFail();
        $this->guardAssignment($assignment, $student, $period->id);

        if ($this->alreadySubmitted($student, $assignment, $period->id)) {
            return redirect()->route('student.evaluations.index')->with('success', 'Evaluasi ini sudah pernah dikirim.');
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $student, $assignment, $period): void {
            $evaluation = $student->evaluations()->create([
                'course_class_assignment_id' => $assignment->id,
                'evaluation_period_id' => $period->id,
                'submitted_at' => now(),
            ]);

            foreach ($data['answers'] as $questionId => $rating) {
                $evaluation->answers()->create([
                    'evaluation_question_id' => (int) $questionId,
                    'star_rating' => (int) $rating,
                ]);
            }

            $evaluation->impression()->create([
                'impression_text' => $data['impression_text'] ?? null,
                'suggestion_text' => $data['suggestion_text'] ?? null,
            ]);
        });

        return redirect()->route('student.evaluations.index')->with('success', 'Evaluasi terkirim. Terima kasih!');
    }

    /**
     * Mahasiswa hanya boleh mengisi assignment kelasnya sendiri di periode open.
     */
    private function guardAssignment(CourseClassAssignment $assignment, ?Student $student, int $periodId): void
    {
        abort_unless(
            $student !== null
            && $assignment->class_group_id === $student->class_group_id
            && $assignment->evaluation_period_id === $periodId,
            403,
        );
    }

    private function alreadySubmitted(Student $student, CourseClassAssignment $assignment, int $periodId): bool
    {
        return $student->evaluations()
            ->where('course_class_assignment_id', $assignment->id)
            ->where('evaluation_period_id', $periodId)
            ->exists();
    }
```

**Route** (grup student):

```php
Route::get('evaluations/{assignment}', [EvaluationController::class, 'show'])->name('evaluations.show');
Route::post('evaluations/{assignment}', [EvaluationController::class, 'store'])->name('evaluations.store');
```

**View `resources/views/student/evaluations/show.blade.php`** (wireframe §4.2):

```blade
<x-student-layout>
    <a href="{{ route('student.evaluations.index') }}" class="text-sm text-accent hover:underline">← Kembali ke daftar</a>

    <div class="mt-3 mb-6">
        <h1 class="font-display text-2xl font-semibold">{{ $assignment->course->name }}</h1>
        <p class="mt-1 text-sm text-muted">
            <span class="font-mono">{{ $assignment->course->code }}</span> · Dosen: {{ $assignment->lecturer->name }}
        </p>
    </div>

    <form method="POST" action="{{ route('student.evaluations.store', $assignment) }}">
        @csrf

        <x-input-error :messages="$errors->get('answers')" class="mb-4" />

        <div class="space-y-6">
            @foreach ($questions->groupBy('category') as $category => $items)
                <x-card>
                    <h2 class="mb-4 text-xs font-semibold uppercase tracking-wide text-muted">{{ $category }}</h2>

                    <div class="space-y-5">
                        @foreach ($items as $question)
                            <div>
                                <p class="mb-2 text-sm text-ink">{{ $question->question_text }}</p>
                                <x-rating-gauge :name="'answers['.$question->id.']'"
                                    :value="old('answers.'.$question->id, 0)" />
                            </div>
                        @endforeach
                    </div>
                </x-card>
            @endforeach

            {{-- Kesan & Saran (GUIDELINE §6.5: dua blok terpisah) --}}
            <x-card>
                <h2 class="mb-4 text-xs font-semibold uppercase tracking-wide text-muted">Kesan & Saran</h2>

                <div class="space-y-4">
                    <div>
                        <x-input-label for="impression_text" :value="'Apa yang paling Anda sukai dari cara mengajar dosen ini?'" />
                        <textarea id="impression_text" name="impression_text" rows="3"
                            class="mt-1 w-full rounded-input border-border bg-surface text-ink text-sm shadow-sm focus:border-accent focus:ring-accent">{{ old('impression_text') }}</textarea>
                    </div>
                    <div>
                        <x-input-label for="suggestion_text" :value="'Apa yang menurut Anda perlu diperbaiki?'" />
                        <textarea id="suggestion_text" name="suggestion_text" rows="3"
                            class="mt-1 w-full rounded-input border-border bg-surface text-ink text-sm shadow-sm focus:border-accent focus:ring-accent">{{ old('suggestion_text') }}</textarea>
                    </div>
                </div>
            </x-card>
        </div>

        <div class="mt-6 flex justify-end">
            <x-button type="submit">Kirim Evaluasi</x-button>
        </div>
    </form>
</x-student-layout>
```

Uji manual: `php artisan migrate:fresh --seed`, login mahasiswa (cari email di tabel `students`→`users`, password `password`) — atau set satu mahasiswa `must_change_password=false` untuk kemudahan. Isi evaluasi, cek gauge & submit.

```bash
vendor/bin/pint --dirty --format agent
git add app/Http/Controllers/Student/EvaluationController.php app/Http/Requests/Student/EvaluationRequest.php routes/web.php resources/views/student/evaluations/show.blade.php
git commit -m "Fase 7: form evaluasi + submit (guard, anti-submit-ganda)

Form per assignment (gauge per pertanyaan + kesan/saran, wireframe §4.2).
Submit buat evaluation+answers+impression dalam transaction; guard
kelas/periode (403), cegah submit ganda. Validasi 'semua wajib dinilai'.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

---

## Commit 4 — Feature test + finalisasi TODO

```bash
php artisan make:test Student/EvaluationSubmissionTest --pest --no-interaction
```

**`tests/Feature/Student/EvaluationSubmissionTest.php`**:

```php
<?php

use App\Models\CourseClassAssignment;
use App\Models\Evaluation;
use App\Models\EvaluationPeriod;
use App\Models\EvaluationQuestion;
use App\Models\Lecturer;
use App\Models\Student;

/**
 * Setup: mahasiswa + periode open + assignment untuk kelasnya + pertanyaan.
 *
 * @return array{student: Student, assignment: CourseClassAssignment, questions: \Illuminate\Support\Collection}
 */
function evalScenario(): array
{
    $period = EvaluationPeriod::factory()->open()->create();
    $student = Student::factory()->create();
    $assignment = CourseClassAssignment::factory()->create([
        'class_group_id' => $student->class_group_id,
        'evaluation_period_id' => $period->id,
    ]);
    $questions = EvaluationQuestion::factory()->count(3)->create(['is_active' => true]);

    return compact('student', 'assignment', 'questions');
}

test('mahasiswa melihat daftar evaluasi kelasnya pada periode open', function () {
    ['student' => $student, 'assignment' => $assignment] = evalScenario();

    $this->actingAs($student->user)->get(route('student.evaluations.index'))
        ->assertOk()
        ->assertSee($assignment->course->name)
        ->assertSee($assignment->lecturer->name);
});

test('submit evaluasi membuat evaluation, answers, dan impression', function () {
    ['student' => $student, 'assignment' => $assignment, 'questions' => $questions] = evalScenario();

    $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 5])->all();

    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), [
        'answers' => $answers,
        'impression_text' => 'Penjelasannya jelas.',
        'suggestion_text' => 'Perbanyak latihan.',
    ])->assertRedirect(route('student.evaluations.index'));

    $evaluation = Evaluation::first();
    expect($evaluation)->not->toBeNull()
        ->and($evaluation->student_id)->toBe($student->id);
    expect($evaluation->answers)->toHaveCount(3);
    expect($evaluation->impression->impression_text)->toBe('Penjelasannya jelas.');
});

test('submit tanpa menilai semua pertanyaan ditolak', function () {
    ['student' => $student, 'assignment' => $assignment, 'questions' => $questions] = evalScenario();

    $answers = [$questions->first()->id => 4]; // hanya 1 dari 3

    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), [
        'answers' => $answers,
    ])->assertSessionHasErrors('answers');

    expect(Evaluation::count())->toBe(0);
});

test('cegah submit ganda untuk assignment yang sama', function () {
    ['student' => $student, 'assignment' => $assignment, 'questions' => $questions] = evalScenario();
    $answers = $questions->mapWithKeys(fn ($q) => [$q->id => 5])->all();

    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), ['answers' => $answers]);
    $this->actingAs($student->user)->post(route('student.evaluations.store', $assignment), ['answers' => $answers]);

    expect(Evaluation::count())->toBe(1);
});

test('team teaching muncul sebagai dua form terpisah', function () {
    ['student' => $student, 'assignment' => $assignment] = evalScenario();
    // Dosen kedua untuk MK+kelas+periode yang sama.
    $dosenB = Lecturer::factory()->create();
    $assignmentB = CourseClassAssignment::factory()->create([
        'course_id' => $assignment->course_id,
        'class_group_id' => $assignment->class_group_id,
        'evaluation_period_id' => $assignment->evaluation_period_id,
        'lecturer_id' => $dosenB->id,
    ]);

    $this->actingAs($student->user)->get(route('student.evaluations.index'))
        ->assertSee($assignment->lecturer->name)
        ->assertSee($dosenB->name);

    // Keduanya bisa dibuka.
    $this->actingAs($student->user)->get(route('student.evaluations.show', $assignmentB))->assertOk();
});

test('mahasiswa tidak bisa mengisi assignment kelas lain', function () {
    ['student' => $student] = evalScenario();
    $period = EvaluationPeriod::open()->first();
    $lain = CourseClassAssignment::factory()->create(['evaluation_period_id' => $period->id]); // kelas berbeda

    $this->actingAs($student->user)->get(route('student.evaluations.show', $lain))->assertForbidden();
});
```

Jalankan:

```bash
php artisan test --compact --filter=EvaluationSubmissionTest
php artisan test --compact
```

Semua harus hijau.

**Update TODO.md** — centang semua item Fase 7, update status:

> *Fase 0–7 selesai. Fase 7: modul mahasiswa — daftar evaluasi (done/undone, team teaching), form dengan Rating Gauge signature (§5), submit anti-ganda + guard kelas/periode, layout mahasiswa responsif (§10). Feature test hijau. Siap lanjut Fase 8 (Modul Dosen — dashboard hasil + anonimitas).*

```bash
vendor/bin/pint --dirty --format agent
git add tests/Feature/Student/ TODO.md
git commit -m "Fase 7: feature test pengisian evaluasi + finalisasi TODO

Uji daftar evaluasi, submit sukses (evaluation+answers+impression),
tolak jika belum lengkap, cegah submit ganda, team teaching 2 form,
akses lintas-kelas ditolak. Fase 7 selesai.

Co-Authored-By: Claude Opus 4.8 <noreply@anthropic.com>"
```

Push (opsional): `git push origin master`.

---

## Checklist hal yang mudah terlewat

- [ ] **Import lengkap** di controller (`EvaluationRequest`, `Student`, `CourseClassAssignment`, `EvaluationQuestion`, `DB`, `RedirectResponse`) & `EvaluationController` di `web.php`.
- [ ] **`npm run build`** setelah menambah gauge/layout (class Tailwind baru), jika `npm run dev` tidak berjalan.
- [ ] **Nama input gauge** = `answers[{{ $question->id }}]` — controller membaca `$data['answers']` keyed by question id.
- [ ] **Guard `guardAssignment`** dipanggil di `show` DAN `store` (jangan hanya salah satu).
- [ ] **Anti-ganda** dicek eksplisit + dijamin unique constraint DB (dua lapis).
- [ ] Gauge **belah ketupat ⬥, bukan bintang ★** (§5); skor numerik `font-mono`.
- [ ] `EvaluationQuestion::active()` sudah mengurutkan `order_number` (dari Fase 2).
- [ ] `vendor/bin/pint --dirty --format agent` sebelum tiap commit.

> File panduan ini boleh dihapus setelah Fase 7 kelar (opsional): `git rm PANDUAN_FASE_7.md`.
