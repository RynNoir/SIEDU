@php $editing = isset($period) && $period->exists; @endphp

<form method="POST"
    action="{{ $editing ? route('admin.evaluation-periods.update', $period) : route('admin.evaluation-periods.store') }}">
    @csrf
    @if ($editing) @method('PUT') @endif

    <div class="space-y-4">
        <div>
            <x-input-label for="name" :value="'Nama Periode'" />
            <x-text-input id="name" name="name" class="mt-1"
                :value="old('name', $period->name ?? '')" required placeholder="mis. Ganjil 2025/2026" />
            <x-input-error :messages="$errors->get('name')" class="mt-1" />
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="academic_year" :value="'Tahun Ajaran'" />
                <x-text-input id="academic_year" name="academic_year" class="mt-1 font-mono"
                    :value="old('academic_year', $period->academic_year ?? '2025/2026')" required />
                <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="semester_type" :value="'Jenis Semester'" />
                <x-select id="semester_type" name="semester_type" class="mt-1" required>
                    @foreach (\App\Enums\SemesterType::cases() as $type)
                        <option value="{{ $type->value }}"
                            @selected(old('semester_type', $period->semester_type->value ?? 'ganjil') === $type->value)>
                            {{ ucfirst($type->value) }}
                        </option>
                    @endforeach
                </x-select>
                <x-input-error :messages="$errors->get('semester_type')" class="mt-1" />
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <x-input-label for="start_date" :value="'Tanggal Mulai'" />
                <x-text-input id="start_date" name="start_date" type="date" class="mt-1"
                    :value="old('start_date', optional($period->start_date ?? null)->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('start_date')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="end_date" :value="'Tanggal Selesai'" />
                <x-text-input id="end_date" name="end_date" type="date" class="mt-1"
                    :value="old('end_date', optional($period->end_date ?? null)->format('Y-m-d'))" required />
                <x-input-error :messages="$errors->get('end_date')" class="mt-1" />
            </div>
        </div>
    </div>

    <div class="mt-6 flex items-center gap-3">
        <x-button type="submit">{{ $editing ? 'Simpan Perubahan' : 'Tambah Periode' }}</x-button>
        <x-button variant="secondary" :href="route('admin.evaluation-periods.index')">Batal</x-button>
    </div>
</form>