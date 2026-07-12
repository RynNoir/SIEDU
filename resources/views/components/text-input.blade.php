@props(['disabled' => false])

<input @disabled($disabled)
    {{ $attributes->merge(['class' => 'w-full rounded-input border-border bg-surface text-ink text-sm shadow-sm transition duration-150 ease-out-quart focus:border-accent focus:ring-accent placeholder:text-muted disabled:bg-canvas disabled:text-muted disabled:cursor-not-allowed']) }}>
