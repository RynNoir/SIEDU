<x-guest-layout>
    <div class="mb-8">
        <h1 class="font-display text-2xl font-semibold text-ink">Selamat Datang Kembali</h1>
        <p class="mt-1 text-sm text-muted">Gunakan akun yang diberikan admin untuk masuk.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div x-data="{ show: false }">
            <x-input-label for="password" :value="'Kata Sandi'" />
            <div class="relative mt-1">
                <x-text-input id="password" class="block w-full pr-10"
                    type="password" x-bind:type="show ? 'text' : 'password'"
                    name="password" required autocomplete="current-password" />
                <button type="button" @click="show = ! show"
                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-muted hover:text-ink"
                    :aria-label="show ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi'">
                    <x-icon x-show="! show" name="eye" class="size-5" />
                    <x-icon x-show="show" name="eye-off" class="size-5" x-cloak />
                </button>
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me + Lupa Password -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center gap-2">
                <input id="remember_me" type="checkbox" class="rounded border-border text-accent shadow-sm focus:ring-accent" name="remember">
                <span class="text-sm text-muted">Ingat saya</span>
            </label>

            @if (Route::has('password.request'))
                <a class="rounded-md text-sm text-accent hover:underline focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent" href="{{ route('password.request') }}">
                    Lupa kata sandi?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full justify-center">
            Masuk
        </x-primary-button>
    </form>
</x-guest-layout>
