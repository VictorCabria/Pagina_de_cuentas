<x-layouts.admin title="Combos">
    <x-slot:actions>
        <a href="{{ route('admin.combos.create') }}" class="btn-primary">+ Nuevo combo</a>
    </x-slot:actions>

    @if ($combos->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">Todavía no hay combos.</p>
            <p class="mt-1 text-sm">Junta varios planes (por ejemplo Disney+ + Crunchyroll) y véndelos a un precio especial.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Combo</th>
                        <th class="hidden px-4 py-3 md:table-cell">Incluye</th>
                        <th class="px-4 py-3">Precio ({{ $currency->value }})</th>
                        <th class="px-4 py-3">Stock</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($combos as $combo)
                        @php($savings = $combo->savings($currency))
                        <tr>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <x-sellable-logo :item="$combo" class="size-10 text-[8px]" />
                                    <div>
                                        <p class="font-medium text-mist-100">
                                            {{ $combo->name }}
                                            @if ($combo->is_featured)
                                                <x-badge color="pink" class="ml-1">Más popular</x-badge>
                                            @endif
                                            @unless ($combo->is_active)
                                                <x-badge class="ml-1">Oculto</x-badge>
                                            @endunless
                                        </p>
                                        <p class="text-xs">{{ $combo->durationLabel() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="hidden px-4 py-3 text-xs md:table-cell">{{ $combo->includedLabel() ?: '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <p class="font-semibold text-mist-100">{{ $combo->price($currency)->formatted() }}</p>
                                @if ($savings > 0)
                                    <p class="text-xs text-pink-300">Ahorro {{ $currency->format($savings) }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-stock-toggle :action="route('admin.combos.toggle', $combo)" :available="$combo->is_available" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.combos.edit', $combo) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                    <form method="POST" action="{{ route('admin.combos.destroy', $combo) }}"
                                          onsubmit="return confirm('¿Eliminar el combo «{{ addslashes($combo->name) }}»?')">
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
        <p class="form-hint mt-3">Un combo se muestra agotado si lo marcas así o si alguno de sus planes está agotado.</p>
    @endif
</x-layouts.admin>
