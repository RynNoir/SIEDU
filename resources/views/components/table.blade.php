{{-- Header bg-canvas sticky (§6.3), tanpa zebra-stripe, shadow+radius besar. Baris hover accent-soft. --}}
<div class="max-h-[70vh] overflow-auto rounded-card bg-surface shadow-md">
    <table class="min-w-full border-collapse text-sm">
        <thead class="sticky top-0 z-10 bg-canvas">
            <tr class="text-left shadow-[0_1px_0_0_var(--color-border)]">
                {{ $head }}
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            {{ $slot }}
        </tbody>
    </table>
</div>
