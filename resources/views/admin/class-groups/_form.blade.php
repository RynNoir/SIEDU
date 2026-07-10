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