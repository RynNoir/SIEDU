<x-admin-layout header="Dashboard Admin">
    <x-card>
        <p class="text-ink">Selamat datang, {{ auth()->user()->name }}.</p>
        <p class="mt-1 text-sm text-muted">Kelola master data lewat menu di samping.</p>
    </x-card>
</x-admin-layout>