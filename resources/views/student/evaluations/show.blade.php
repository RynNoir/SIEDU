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