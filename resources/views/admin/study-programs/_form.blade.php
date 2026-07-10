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