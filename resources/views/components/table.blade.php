{{-- Header bg-canvas, tanpa zebra-stripe, shadow+radius besar (bukan border) ala Elegent. --}}
<div class="overflow-x-auto rounded-card bg-surface shadow-md">
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
