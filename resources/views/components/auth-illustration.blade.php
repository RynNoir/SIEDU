{{-- Panel ilustrasi auth: bentuk geometris + motif diamond rating (§5), token GUIDELINE saja. --}}
<div class="relative hidden overflow-hidden bg-ink lg:block">
    <svg viewBox="0 0 400 600" preserveAspectRatio="xMidYMid slice" class="absolute inset-0 h-full w-full" aria-hidden="true">
        <rect width="400" height="600" fill="var(--color-ink)" />

        {{-- Blok diagonal aksen --}}
        <path d="M0 0 H270 L170 230 H0 Z" fill="var(--color-accent)" />
        <path d="M270 0 H400 V150 L300 230 L170 230 Z" fill="var(--color-accent-soft)" opacity="0.18" />

        {{-- Grid titik (kesan blueprint/datasheet teknis, §1) --}}
        @for ($row = 0; $row < 6; $row++)
            @for ($col = 0; $col < 5; $col++)
                <circle cx="{{ 300 + $col * 20 }}" cy="{{ 40 + $row * 20 }}" r="1.6" fill="var(--color-accent-soft)" opacity="0.4" />
            @endfor
        @endfor

        {{-- Klaster diamond — motif Rating Gauge diperbesar sebagai hero art --}}
        <g transform="translate(300 400)">
            <path d="M0 -70 L52 0 L0 70 L-52 0 Z" fill="var(--color-rating)" />
        </g>
        <g transform="translate(215 460) scale(0.55)" opacity="0.6">
            <path d="M0 -70 L52 0 L0 70 L-52 0 Z" fill="var(--color-accent-soft)" />
        </g>
        <g transform="translate(340 490) scale(0.32)" opacity="0.8">
            <path d="M0 -70 L52 0 L0 70 L-52 0 Z" fill="var(--color-rating)" />
        </g>

        {{-- Lingkaran aksen --}}
        <circle cx="80" cy="470" r="75" fill="var(--color-accent)" />
        <circle cx="80" cy="470" r="75" fill="none" stroke="var(--color-accent-soft)" stroke-width="1" opacity="0.4" />

        {{-- Garis tipis horizontal (struktur datasheet, GUIDELINE §4.1) --}}
        <line x1="0" y1="540" x2="400" y2="540" stroke="var(--color-accent-soft)" stroke-width="1" opacity="0.25" />
        <line x1="0" y1="555" x2="400" y2="555" stroke="var(--color-accent-soft)" stroke-width="1" opacity="0.15" />
    </svg>

    <div class="absolute inset-0 flex flex-col justify-end p-10 xl:p-14">
        <blockquote class="max-w-sm font-display text-2xl font-semibold leading-tight text-white">
            &ldquo;Evaluasi sebagai pembacaan instrumen, bukan rating konsumen.&rdquo;
        </blockquote>
        <p class="mt-4 text-sm text-canvas/60">Politeknik Negeri Padang · Jurusan Teknologi Informasi</p>
    </div>
</div>
