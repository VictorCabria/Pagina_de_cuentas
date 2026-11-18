{{-- Widget flotante de soporte por WhatsApp: botón con pulso, mensaje de bienvenida y mini chat --}}
@props(['number', 'display', 'storeName'])

@php
    $icon = 'M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.28-.1-.48-.15-.68.15-.2.3-.78.97-.95 1.17-.18.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.58-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35ZM12.04 21.5h-.01a9.4 9.4 0 0 1-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.43 9.43 0 0 1 14.67-11.6 9.36 9.36 0 0 1 2.76 6.66 9.43 9.43 0 0 1-9.44 9.36Zm8.03-17.4A11.27 11.27 0 0 0 12.04.75 11.33 11.33 0 0 0 2.23 17.7L.75 23.25l5.68-1.49a11.3 11.3 0 0 0 5.6 1.43h.01A11.33 11.33 0 0 0 23.35 11.9a11.26 11.26 0 0 0-3.28-7.8Z';
    $chatUrl = 'https://wa.me/'.$number.'?text='.rawurlencode('Hola, tengo una duda sobre '.$storeName.'.');
@endphp

<div data-wa class="fixed right-4 bottom-4 z-50 flex flex-col items-end gap-3 sm:right-6 sm:bottom-6">
    {{-- Mini chat --}}
    <div data-wa-panel id="wa-panel" role="dialog" aria-label="Soporte por WhatsApp"
         class="wa-pop w-[min(22rem,calc(100vw-2rem))] origin-bottom-right overflow-hidden rounded-3xl bg-shop-card shadow-2xl shadow-black/60 ring-1 ring-white/10">
        <div class="flex items-center gap-3 bg-gradient-to-r from-[#128c7e] to-[#25d366] px-5 py-4 text-white">
            <span class="relative grid size-11 shrink-0 place-items-center">
                <x-brand-mark class="size-11" />
                <span class="absolute right-0 bottom-0 size-3 rounded-full bg-lime-300 ring-2 ring-[#1fae5a]"></span>
            </span>
            <div class="min-w-0 flex-1">
                <p class="truncate font-semibold">{{ $storeName }}</p>
                <p class="text-xs text-white/85">En línea · responde en minutos</p>
            </div>
            <button type="button" data-wa-close class="grid size-8 place-items-center rounded-full text-white/90 transition hover:bg-white/20" aria-label="Cerrar">
                <svg class="size-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
            </button>
        </div>

        <div class="wa-wallpaper space-y-2 px-4 py-5">
            <div data-wa-typing class="inline-flex items-center gap-1 rounded-2xl rounded-tl-sm bg-white/10 px-4 py-3" aria-label="Escribiendo">
                <span class="wa-dot"></span><span class="wa-dot [animation-delay:150ms]"></span><span class="wa-dot [animation-delay:300ms]"></span>
            </div>
            <div data-wa-message hidden class="wa-message max-w-[85%] rounded-2xl rounded-tl-sm bg-white/10 px-4 py-3 text-sm text-slate-100">
                <p class="font-semibold text-[#25d366]">{{ $storeName }}</p>
                <p class="mt-1">¡Hola! 👋 Bienvenido. ¿Tienes alguna duda sobre un plan, el pago o la entrega? Escríbenos y te ayudamos.</p>
                <p class="mt-1 text-right text-[10px] text-slate-400">ahora</p>
            </div>
        </div>

        <div class="border-t border-white/10 p-4">
            <a href="{{ $chatUrl }}" target="_blank" rel="noopener"
               class="flex w-full items-center justify-center gap-2 rounded-full bg-[#25d366] px-5 py-3 font-semibold text-white shadow-lg shadow-[#25d366]/25 transition hover:-translate-y-0.5 hover:bg-[#1ebe5b]">
                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icon }}"/></svg>
                Iniciar chat
            </a>
            <p class="mt-2.5 text-center text-xs text-slate-400">o escríbenos al <strong class="text-slate-200">{{ $display }}</strong></p>
        </div>
    </div>

    {{-- Mensaje de bienvenida que aparece solo --}}
    <div data-wa-teaser class="wa-pop relative flex max-w-[16rem] origin-bottom-right items-start gap-2 rounded-2xl rounded-br-sm bg-white px-4 py-3 text-sm text-slate-800 shadow-xl shadow-black/40">
        <button type="button" data-wa-open class="text-left">
            <span class="block font-semibold text-slate-900">¡Hola! 👋 ¿Tienes dudas?</span>
            <span class="block text-slate-600">Escríbenos, te respondemos en minutos.</span>
        </button>
        <button type="button" data-wa-dismiss class="-mt-1 -mr-2 grid size-6 shrink-0 place-items-center rounded-full text-slate-400 hover:bg-slate-100 hover:text-slate-700" aria-label="Ocultar mensaje">
            <svg class="size-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
        </button>
    </div>

    {{-- Botón --}}
    <button type="button" data-wa-toggle aria-expanded="false" aria-controls="wa-panel" aria-label="Soporte por WhatsApp: {{ $display }}"
            class="wa-pulse relative grid size-15 place-items-center rounded-full bg-[#25d366] text-white shadow-xl shadow-black/40 transition hover:scale-105 hover:bg-[#1ebe5b]">
        <svg data-wa-icon-chat class="size-8 transition duration-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="{{ $icon }}"/></svg>
        <svg data-wa-icon-close class="absolute size-7 scale-50 opacity-0 transition duration-300" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z"/></svg>
        <span data-wa-badge class="absolute -top-0.5 -right-0.5 grid size-5 place-items-center rounded-full bg-rose-500 text-[11px] font-bold ring-2 ring-shop-bg">1</span>
    </button>
</div>
