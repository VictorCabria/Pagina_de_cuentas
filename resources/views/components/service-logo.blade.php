{{-- Logo del servicio o, si no tiene, su inicial sobre el color de marca --}}
@props(['service'])

@if ($service->logoUrl())
    <img src="{{ $service->logoUrl() }}" alt="{{ $service->name }}"
         {{ $attributes->class('shrink-0 rounded-xl object-contain') }}
         style="background-color: {{ $service->color }}1a">
@else
    <span {{ $attributes->class('grid shrink-0 place-items-center rounded-xl font-display font-bold text-white') }}
          style="background-color: {{ $service->color }}">{{ $service->initials() }}</span>
@endif
