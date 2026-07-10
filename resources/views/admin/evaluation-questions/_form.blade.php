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