<x-guest-layout>
    <div class="mb-4 text-sm text-muted">
        Terima kasih. Sebelum mulai, verifikasi alamat email Anda dengan mengeklik tautan yang baru kami kirim. Jika email belum diterima, kami akan mengirim ulang.
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 text-sm font-medium text-success">
            Tautan verifikasi baru telah dikirim ke alamat email Anda.
        </div>
    @endif

    <div class="mt-4 flex items-center justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-button>Kirim Ulang Email Verifikasi</x-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="rounded-md text-sm text-muted underline hover:text-ink focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-accent">
                Keluar
            </button>
        </form>
    </div>
</x-guest-layout>
