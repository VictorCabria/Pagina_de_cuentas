{{-- Logo de la tienda: bolsa de compras con botón de play (mismo dibujo que public/favicon.svg) --}}
@php
    $id = 'bm'.\Illuminate\Support\Str::random(6);
@endphp
<svg {{ $attributes->merge(['class' => 'shrink-0 drop-shadow-[0_4px_10px_rgba(34,211,238,0.3)]']) }} viewBox="0 0 64 64" aria-hidden="true">
    <defs>
        <linearGradient id="{{ $id }}-bg" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#22d3ee"/>
            <stop offset=".55" stop-color="#2563eb"/>
            <stop offset="1" stop-color="#4c1d95"/>
        </linearGradient>
        <linearGradient id="{{ $id }}-play" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#f472b6"/>
            <stop offset="1" stop-color="#e11d48"/>
        </linearGradient>
    </defs>
    <rect width="64" height="64" rx="16" fill="url(#{{ $id }}-bg)"/>
    <path d="M24 25v-4a8 8 0 0 1 16 0v4" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/>
    <path d="M17.5 23h29a3 3 0 0 1 3 2.8l1.6 22.4A5 5 0 0 1 46.1 54H17.9a5 5 0 0 1-5-5.8l1.6-22.4a3 3 0 0 1 3-2.8Z" fill="#fff"/>
    <path d="M28 32.3v12.4a1.6 1.6 0 0 0 2.4 1.4l10.2-6.2a1.6 1.6 0 0 0 0-2.8l-10.2-6.2a1.6 1.6 0 0 0-2.4 1.4Z" fill="url(#{{ $id }}-play)"/>
    <path d="M51 7.5l1.4 3.6 3.6 1.4-3.6 1.4L51 17.5l-1.4-3.6-3.6-1.4 3.6-1.4Z" fill="#fde68a"/>
</svg>
