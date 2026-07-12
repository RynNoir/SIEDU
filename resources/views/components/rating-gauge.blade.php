@props(['name', 'value' => 0, 'max' => 5])

{{-- Gauge interaktif: 5 belah ketupat ⬥ (bukan bintang). Kosong=border, terisi=rating (amber).
     Keyboard: Tab antar notch, Enter/Space memilih. Target sentuh ≥44px, diperbesar di mobile
     (size-12/48px) sesuai GUIDELINE §10 -- baris notch & skor ditumpuk di mobile supaya notch
     bisa lebih besar tanpa risiko overflow horizontal di layar 320px. --}}
<div x-data="{ rating: {{ (int) $value }}, hover: 0 }" class="flex flex-col gap-2 sm:flex-row sm:items-center sm:gap-3">
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
                class="flex size-12 items-center justify-center rounded-input transition duration-150 ease-out-quart focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-accent sm:size-11">
                <span class="text-3xl leading-none transition-colors duration-150 sm:text-2xl"
                    :class="(hover || rating) >= {{ $i }} ? 'text-rating' : 'text-border'">⬥</span>
            </button>
        @endfor
    </div>

    <span class="font-mono text-sm text-muted"
        x-text="rating ? rating + ' / {{ $max }}' : '– / {{ $max }}'"></span>
</div>
