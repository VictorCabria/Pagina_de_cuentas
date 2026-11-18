<x-layouts.admin title="Promociones">
    <x-slot:actions>
        <a href="{{ route('admin.promotions.create') }}" class="btn-primary">+ Nueva promoción</a>
    </x-slot:actions>

    @if ($promotions->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">No hay promociones.</p>
            <p class="mt-1 text-sm">Crea un descuento del 1 al 100 % para toda la tienda, una categoría, un servicio o un plan.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Promoción</th>
                        <th class="px-4 py-3">Descuento</th>
                        <th class="hidden px-4 py-3 md:table-cell">Vigencia</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($promotions as $promotion)
                        @php($status = $promotion->status())
                        <tr>
                            <td class="px-4 py-3">
                                <p class="font-medium text-mist-100">{{ $promotion->name }}</p>
                                <p class="text-xs">{{ $promotion->targetLabel() }}</p>
                            </td>
                            <td class="px-4 py-3 text-lg font-bold text-pink-300">-{{ $promotion->percent }}%</td>
                            <td class="hidden px-4 py-3 text-xs md:table-cell">
                                {{ $promotion->starts_at?->format('d/m/Y H:i') ?? 'Desde ya' }}<br>
                                {{ $promotion->ends_at ? 'hasta '.$promotion->ends_at->format('d/m/Y H:i') : 'Sin fecha de fin' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-badge :color="match ($status) { 'Activa' => 'green', 'Programada' => 'blue', 'Vencida' => 'red', default => 'gray' }">{{ $status }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <form method="POST" action="{{ route('admin.promotions.toggle', $promotion) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button class="btn-ghost px-2.5 py-1 text-xs">{{ $promotion->is_active ? 'Pausar' : 'Activar' }}</button>
                                    </form>
                                    <a href="{{ route('admin.promotions.edit', $promotion) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                    <form method="POST" action="{{ route('admin.promotions.destroy', $promotion) }}"
                                          onsubmit="return confirm('¿Eliminar la promoción «{{ addslashes($promotion->name) }}»?')">
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
        <p class="form-hint mt-3">Si varias promociones aplican al mismo plan, se usa la de mayor porcentaje (no se suman).</p>
    @endif
</x-layouts.admin>
