{{-- Header bg-canvas, border 1px, tanpa zebra-stripe. Slot 'head' untuk <th>, slot default untuk <tr> body. --}}
<div class="overflow-x-auto rounded-card border border-border bg-surface">
    <table class="min-w-full border-collapse text-sm">
        <thead class="bg-canvas">
            <tr class="text-left">
                {{ $head }}
            </tr>
        </thead>
        <tbody class="divide-y divide-border">
            {{ $slot }}
        </tbody>
    </table>
</div>