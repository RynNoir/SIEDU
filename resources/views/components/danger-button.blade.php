<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 rounded-input border border-danger px-5 py-2.5 text-sm font-medium text-danger transition duration-150 hover:bg-danger/10 focus:outline-none focus:ring-2 focus:ring-danger focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
