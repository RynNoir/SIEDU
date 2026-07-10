<x-admin-layout header="Promosi Kelas Tahunan">
    <x-card class="max-w-xl">
        <p class="text-sm text-muted">
            Menaikkan semua kelas dari tahun ajaran asal ke tahun berikutnya. Mahasiswa aktif naik tingkat
            (+2 semester); mahasiswa cuti & DO tetap di kelas lama. Kelas tahun akhir tidak dinaikkan.
            Aman dijalankan berulang.
        </p>

        <form method="POST" action="{{ route('admin.class-promotion.run') }}" class="mt-6 space-y-4"
            onsubmit="return confirm('Jalankan promosi kelas? Tindakan ini memindahkan mahasiswa aktif ke tahun berikutnya.')">
            @csrf

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <x-input-label for="from_year" :value="'Tahun Ajaran Asal'" />
                    <x-text-input id="from_year" name="from_year" class="mt-1 font-mono"
                        :value="old('from_year', '2025/2026')" required />
                    <x-input-error :messages="$errors->get('from_year')" class="mt-1" />
                </div>
                <div>
                    <x-input-label for="to_year" :value="'Tahun Ajaran Tujuan'" />
                    <x-text-input id="to_year" name="to_year" class="mt-1 font-mono"
                        :value="old('to_year', '2026/2027')" required />
                    <x-input-error :messages="$errors->get('to_year')" class="mt-1" />
                </div>
            </div>

            <x-button type="submit">Jalankan Promosi</x-button>
        </form>
    </x-card>
</x-admin-layout>