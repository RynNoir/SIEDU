<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-ink leading-tight">Dashboard Mahasiswa</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-surface border border-border rounded-lg shadow-sm">
                <div class="p-6 text-ink">
                    Selamat datang, {{ auth()->user()->name }}. di Dashboard
                </div>
            </div>
        </div>
    </div>
</x-app-layout>