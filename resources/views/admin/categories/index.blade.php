<x-layouts.admin title="Categorías">
    <x-slot:actions>
        <a href="{{ route('admin.categories.create') }}" class="btn-primary">+ Nueva categoría</a>
    </x-slot:actions>

    @if ($categories->isEmpty())
        <div class="card text-center">
            <p class="text-mist-100">Todavía no hay categorías.</p>
            <p class="mt-1 text-sm">Agrupan los servicios en la tienda: Streaming, Música, IA...</p>
        </div>
    @else
        <div class="overflow-hidden rounded-xl border border-ink-700">
            <table class="w-full text-left text-sm">
                <thead class="bg-ink-900 text-xs uppercase tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Servicios</th>
                        <th class="hidden px-4 py-3 sm:table-cell">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-ink-700 bg-ink-800/30">
                    @foreach ($categories as $category)
                        <tr>
                            <td class="px-4 py-3">
                                <span class="mr-1">{{ $category->icon }}</span>
                                <span class="font-medium text-mist-100">{{ $category->name }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.services.index', ['categoria' => $category->id]) }}" class="hover:text-accent">{{ $category->services_count }}</a>
                            </td>
                            <td class="hidden px-4 py-3 sm:table-cell">
                                <x-badge :color="$category->is_active ? 'green' : 'gray'">{{ $category->is_active ? 'Visible' : 'Oculta' }}</x-badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <a href="{{ route('admin.categories.edit', $category) }}" class="btn-ghost px-2.5 py-1 text-xs">Editar</a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                          onsubmit="return confirm('¿Eliminar la categoría «{{ addslashes($category->name) }}»? Sus servicios no se borran.')">
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
    @endif
</x-layouts.admin>
