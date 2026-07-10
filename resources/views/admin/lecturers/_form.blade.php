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