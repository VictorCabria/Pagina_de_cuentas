{{-- Logo de un plan (el de su servicio) o miniatura de un combo (su imagen o los logos que incluye) --}}
@props(['item'])

@if ($item instanceof \App\Models\Combo)
    @if ($item->imageUrl())
        <img src="{{ $item->imageUrl() }}" alt="{{ $item->name }}" {{ $attributes->class('shrink-0 rounded-xl object-cover') }}>
    @else
        <span {{ $attributes->class('relative grid shrink-0 grid-cols-2 gap-0.5 overflow-hidden rounded-xl bg-gradient-to-br from-blue-600 via-indigo-600 to-cyan-500 p-1.5') }}>
            @foreach ($item->plans->pluck('service')->unique('id')->take(4) as $service)
                <x-service-logo :service="$service" class="size-full rounded-md text-[10px]" />
            @endforeach
        </span>
    @endif
@else
    <x-service-logo :service="$item->service" {{ $attributes }} />
@endif
