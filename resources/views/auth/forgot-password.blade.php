<x-guest-layout>
    <div class="mb-4 text-sm text-muted">
        Lupa kata sandi? Masukkan alamat email Anda, dan kami akan mengirim tautan untuk mengatur ulang kata sandi.
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <x-button>Kirim Tautan Reset</x-button>
        </div>
    </form>
</x-guest-layout>
