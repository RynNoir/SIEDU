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