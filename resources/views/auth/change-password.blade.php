<x-guest-layout>
    <div class="mb-4 text-sm text-muted">
        Demi keamanan, Anda wajib mengganti password default sebelum melanjutkan.
    </div>

    <form method="POST" action="{{ route('password.change.update') }}">
        @csrf
        @method('PUT')

        <div>
            <x-input-label for="password" :value="'Password Baru'" />
            <x-text-input id="password" name="password" type="password"
                class="mt-1 block w-full" required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="'Konfirmasi Password'" />
            <x-text-input id="password_confirmation" name="password_confirmation" type="password"
                class="mt-1 block w-full" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-6 flex justify-end">
            <x-primary-button>{{ __('Simpan Password') }}</x-primary-button>
        </div>
    </form>

    {{-- Form logout terpisah (form tidak boleh bersarang) --}}
    <form method="POST" action="{{ route('logout') }}" class="mt-4">
        @csrf
        <button type="submit" class="text-sm text-muted underline">Keluar</button>
    </form>
</x-guest-layout>