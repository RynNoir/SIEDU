<section class="space-y-4">
    <header>
        <h2 class="font-display text-lg font-semibold text-ink">
            Hapus Akun
        </h2>

        <p class="mt-1 text-sm text-muted">
            Setelah akun dihapus, seluruh data terkait akan dihapus permanen. Unduh data yang ingin Anda simpan sebelum menghapus akun.
        </p>
    </header>

    <x-button
        variant="destructive"
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Hapus Akun</x-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="font-display text-lg font-semibold text-ink">
                Yakin ingin menghapus akun ini?
            </h2>

            <p class="mt-1 text-sm text-muted">
                Setelah akun dihapus, seluruh data terkait akan dihapus permanen. Masukkan kata sandi Anda untuk konfirmasi.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Kata Sandi" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-3/4"
                    placeholder="Kata Sandi"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-button variant="secondary" x-on:click="$dispatch('close')">
                    Batal
                </x-button>

                <x-button variant="destructive">
                    Hapus Akun
                </x-button>
            </div>
        </form>
    </x-modal>
</section>
