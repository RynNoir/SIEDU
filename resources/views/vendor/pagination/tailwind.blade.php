@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigasi halaman" class="flex items-center justify-between">
        {{-- Mobile: Prev / Next --}}
        <div class="flex flex-1 justify-between sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex cursor-default items-center rounded-full bg-surface px-4 py-2 text-sm text-muted shadow-sm">
                    Sebelumnya
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="relative inline-flex items-center rounded-full bg-surface px-4 py-2 text-sm text-ink shadow-sm hover:bg-accent-soft">
                    Sebelumnya
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="relative ml-3 inline-flex items-center rounded-full bg-surface px-4 py-2 text-sm text-ink shadow-sm hover:bg-accent-soft">
                    Berikutnya
                </a>
            @else
                <span class="relative ml-3 inline-flex cursor-default items-center rounded-full bg-surface px-4 py-2 text-sm text-muted shadow-sm">
                    Berikutnya
                </span>
            @endif
        </div>

        {{-- Desktop --}}
        <div class="hidden sm:flex sm:flex-1 sm:items-center sm:justify-between">
            <div>
                <p class="text-sm text-muted">
                    {!! __('Menampilkan') !!}
                    <span class="font-mono text-ink">{{ $paginator->firstItem() }}</span>
                    –
                    <span class="font-mono text-ink">{{ $paginator->lastItem() }}</span>
                    {!! __('dari') !!}
                    <span class="font-mono text-ink">{{ $paginator->total() }}</span>
                    {!! __('hasil') !!}
                </p>
            </div>

            <div>
                <span class="relative z-0 inline-flex gap-1.5">
                    {{-- Previous --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">
                            <span class="relative inline-flex size-9 cursor-default items-center justify-center rounded-full bg-surface text-sm text-border shadow-sm" aria-hidden="true">&lsaquo;</span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}"
                            class="relative inline-flex size-9 items-center justify-center rounded-full bg-surface text-sm text-ink shadow-sm hover:bg-accent-soft">&lsaquo;</a>
                    @endif

                    {{-- Nomor halaman --}}
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span aria-disabled="true">
                                <span class="relative inline-flex size-9 cursor-default items-center justify-center rounded-full bg-surface text-sm text-muted shadow-sm">{{ $element }}</span>
                            </span>
                        @endif

                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page">
                                        <span class="relative inline-flex size-9 cursor-default items-center justify-center rounded-full bg-accent text-sm font-medium text-white shadow-sm">{{ $page }}</span>
                                    </span>
                                @else
                                    <a href="{{ $url }}" aria-label="{{ __('Ke halaman :page', ['page' => $page]) }}"
                                        class="relative inline-flex size-9 items-center justify-center rounded-full bg-surface text-sm text-ink shadow-sm hover:bg-accent-soft">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach

                    {{-- Next --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}"
                            class="relative inline-flex size-9 items-center justify-center rounded-full bg-surface text-sm text-ink shadow-sm hover:bg-accent-soft">&rsaquo;</a>
                    @else
                        <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">
                            <span class="relative inline-flex size-9 cursor-default items-center justify-center rounded-full bg-surface text-sm text-border shadow-sm" aria-hidden="true">&rsaquo;</span>
                        </span>
                    @endif
                </span>
            </div>
        </div>
    </nav>
@endif
