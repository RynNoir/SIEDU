<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center gap-2 rounded-input bg-accent px-5 py-2.5 text-sm font-medium text-white transition duration-150 hover:brightness-95 focus:outline-none focus:ring-2 focus:ring-accent focus:ring-offset-2']) }}>
    {{ $slot }}
</button>
