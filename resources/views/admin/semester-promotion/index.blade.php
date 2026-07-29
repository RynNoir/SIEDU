<x-admin-layout header="Promosi Semester">
    <x-card class="max-w-xl">
        <p class="text-sm text-muted">
            Menaikkan mahasiswa aktif <span class="font-medium text-ink">satu semester</span> dari periode yang dipilih.
            Dari <span class="font-medium text-ink">ganjil</span> mahasiswa naik ke semester genap dan tetap di kelas yang sama;
            dari <span class="font-medium text-ink">genap</span> mahasiswa naik kelas satu tingkat di tahun ajaran berikutnya.
            Mahasiswa cuti &amp; DO tidak ikut. Aman dijalankan berulang.
        </p>

        <form method="POST" action="{{ route('admin.semester-promotion.run') }}" class="mt-6 space-y-4"
            onsubmit="return confirm('Jalankan promosi semester? Tindakan ini menaikkan semester mahasiswa aktif.')">
            @csrf

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <x-input-label for="academic_year" :value="'Tahun Ajaran Periode Asal'" />
                    <x-text-input id="academic_year" name="academic_year" class="mt-1 font-mono"
                        :value="old('academic_year', '2025/2026')" required />
                    <x-input-error :messages="$errors->get('academic_year')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="semester_type" :value="'Semester Periode Asal'" />
                    <x-select id="semester_type" name="semester_type" class="mt-1" required>
                        <option value="ganjil" @selected(old('semester_type') === 'ganjil')>Ganjil</option>
                        <option value="genap" @selected(old('semester_type') === 'genap')>Genap</option>
                    </x-select>
                    <x-input-error :messages="$errors->get('semester_type')" class="mt-1" />
                </div>
            </div>

            <x-button type="submit">Jalankan Promosi</x-button>
        </form>
    </x-card>
</x-admin-layout>
