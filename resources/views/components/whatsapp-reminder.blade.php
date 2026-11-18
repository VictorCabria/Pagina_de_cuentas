{{-- Botón verde de WhatsApp con el número del cliente: abre el chat con el recordatorio de vencimiento --}}
@props(['subscription'])

@if ($subscription->reminderUrl())
    <a href="{{ $subscription->reminderUrl() }}" target="_blank" rel="noopener"
       title="Enviarle a {{ $subscription->customer_name }} el recordatorio de vencimiento"
       {{ $attributes->class('inline-flex items-center gap-1.5 rounded-full bg-[#25d366] px-2.5 py-1 text-xs font-semibold whitespace-nowrap text-white shadow-sm shadow-[#25d366]/30 transition hover:bg-[#1ebe5b]') }}>
        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.28-.1-.48-.15-.68.15-.2.3-.78.97-.95 1.17-.18.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.14-.13.3-.35.45-.52.15-.18.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.8.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.27.49 1.7.63.72.23 1.37.2 1.88.12.58-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35ZM12.04 21.5h-.01a9.4 9.4 0 0 1-4.8-1.32l-.34-.2-3.57.94.95-3.48-.22-.36a9.43 9.43 0 0 1 14.67-11.6 9.36 9.36 0 0 1 2.76 6.66 9.43 9.43 0 0 1-9.44 9.36Zm8.03-17.4A11.27 11.27 0 0 0 12.04.75 11.33 11.33 0 0 0 2.23 17.7L.75 23.25l5.68-1.49a11.3 11.3 0 0 0 5.6 1.43h.01A11.33 11.33 0 0 0 23.35 11.9a11.26 11.26 0 0 0-3.28-7.8Z"/></svg>
        {{ $subscription->formattedPhone() }}
    </a>
@endif
