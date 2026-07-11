@props(['name', 'value' => 0, 'max' => 5])

{{-- Gauge interaktif: 5 belah ketupat ⬥ (bukan bintang). Kosong=border, terisi=rating (amber).
     Keyboard: Tab antar notch, Enter/Space memilih. Target sentuh 44px (size-11). --}}
<div x-data="{ rating: {{ (int) $value }}, hover: 0 }" class="flex items-center gap-3">
    <input type="hidden" name="{{ $name }}" :value="rating">

    <div class="flex items-center gap-1" role="radiogroup" aria-label="Nilai 1 sampai {{ $max }}">
        @for ($i = 1; $i <= $max; $i++)
            <button type="button"
                role="radio"
                :aria-checked="rating === {{ $i }}"
                aria-label="Nilai {{ $i }}"
                @click="rating = {{ $i }}"
                @mouseenter="hover = {{ $i }}"
                @mouseleave="hover = 0"
                @keydown.enter.prevent="rating = {{ $i }}"
                @keydown.space.prevent="rating = {{ $i }}"
                class="flex size-11 items-center justify-center rounded-input transition duration-150 focus:outline-none focus:ring-2 focus:ring-accent">
                <span class="text-2xl leading-none transition-colors duration-150"
                    :class="(hover || rating) >= {{ $i }} ? 'text-rating' : 'text-border'">⬥</span>
            </button>
        @endfor
    </div>

    <span class="font-mono text-sm text-muted"
        x-text="rating ? rating + ' / {{ $max }}' : '– / {{ $max }}'"></span>
</div>