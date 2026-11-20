{{-- Cambia la moneda en que se muestran los montos (?moneda=USD|COP) --}}
@props(['current'])

<div class="flex rounded-full bg-ink-800 p-1 text-xs font-semibold" role="group" aria-label="Moneda">
    @foreach (\App\Enums\Currency::cases() as $option)
        <a href="{{ request()->fullUrlWithQuery(['moneda' => $option->value]) }}"
           @class(['rounded-full px-3 py-1 transition', 'bg-accent text-cyan-950' => $current === $option, 'text-mist-300 hover:text-mist-100' => $current !== $option])
           @if ($current === $option) aria-current="true" @endif>
            {{ $option->value }}
        </a>
    @endforeach
</div>
