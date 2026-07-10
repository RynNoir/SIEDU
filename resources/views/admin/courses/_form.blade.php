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