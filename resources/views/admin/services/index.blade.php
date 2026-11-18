<x-layouts.admin title="Servicios">
    <x-slot:actions>
        <a href="{{ route('admin.services.create') }}" class="btn-primary">+ Nuevo servicio</a>
    </x-slot:actions>

    <form method="GET" class="mb-4 flex flex-wrap gap-2">
        <select name="categoria" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $category)
                <option value="{{ $category->id }}" @selected(request('categoria') == $category->id)>{{ $category->name }}</option>
            @endforeach
        </select>
        <select name="estado" class="form-input w-auto" onchange="this.form.submit()">
            <option value="">Todos los estados</option>
            <option value="disponibles" @selected(request('estado') === 'disponibles')>Disponibles</option>
            <option value="agotados" @selected(request('estado') === 'agotados')>Agotados</option>
        </select>
    </form>

    @if ($services->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">No hay servicios con ese filtro.</p>
            <p class="mt-1 text-sm">Crea uno (Netflix, Spotify, ChatGPT...) y luego agrégale sus planes.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Servicio</th>
                        <th class="hidden px-4 py-3 md:table-cell">Planes</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Desde ({{ $currency->value }})</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($services as $service)
                        @php($cheapest = $service->plans->map->price($currency)->sortBy('final')->first())
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-service-logo :service="$service" class="size-10" />
                                    <div class="min-w-0">
                                        <p class="flex flex-wrap items-center gap-1.5 font-medium text-mist-100">
                                            {{ $service->name }}
                                            @if (! $service->is_active)
                                                <x-badge>Oculto</x-badge>
                                            @endif
                                            @if ($service->is_featured)
                                                <x-badge color="blue">Destacado</x-badge>
                                            @endif
                                        </p>
                                        <p class="text-xs">{{ $service->category?->name ?? 'Sin categoría' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 md:table-cell">
                                {{ $service->plans->where('is_available', true)->count() }}/{{ $service->plans->count() }} disponibles
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                @if ($cheapest)
                                    <span class="text-mist-100">{{ $cheapest->formatted() }}</span>
                                    @if ($cheapest->hasDiscount())
                                        <x-badge color="pink">-{{ $cheapest->discount }}%</x-badge>
                                    @endif
                                @else
                                    <span class="text-xs text-amber-300">Sin planes</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-stock-toggle :action="route('admin.services.toggle', $service)" :available="$service->is_available" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.services.edit', $service) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                    <form method="POST" action="{{ route('admin.services.destroy', $service) }}"
                                          onsubmit="return confirm('¿Eliminar «{{ addslashes($service->name) }}» y todos sus planes?')">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn-danger px-2.5 py-1 text-xs">Eliminar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <p class="form-hint mt-3">Haz clic en «Disponible / Agotado» para cambiar el stock al instante.</p>
    @endif
</x-layouts.admin>
