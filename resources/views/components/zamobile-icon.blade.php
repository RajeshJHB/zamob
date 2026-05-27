@props(['class' => 'h-5 w-5 shrink-0'])

<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 32 32', 'aria-hidden' => 'true']) }}>
    <rect width="32" height="32" rx="7" fill="#DC2626"/>
    <path fill="#FFFFFF" d="M8.5 8h15v2.8h-6.8L23.5 24h-3.6L10.2 12.4V24H8.5V8z"/>
    <rect x="11" y="26" width="10" height="1.8" rx="0.9" fill="#FFFFFF" opacity="0.85"/>
</svg>
