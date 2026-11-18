<x-layouts.admin :title="$category->exists ? 'Editar categoría' : 'Nueva categoría'">
    <form method="POST"
          action="{{ $category->exists ? route('admin.categories.update', $category) : route('admin.categories.store') }}"
          class="card max-w-2xl space-y-5">
        @csrf
        @if ($category->exists)
            @method('PUT')
        @endif

        <div class="grid gap-5 sm:grid-cols-[1fr_8rem]">
            <x-field name="name" label="Nombre" :value="$category->name" required maxlength="80" placeholder="Streaming" />
            <x-field name="icon" label="Ícono" :value="$category->icon" maxlength="40" placeholder="🎬" hint="Un emoji." />
        </div>
        <div class="grid gap-5 sm:grid-cols-2">
            <x-field name="slug" label="Slug (URL)" :value="$category->slug" hint="Vacío = se genera del nombre." />
            <x-field name="sort_order" label="Orden" type="number" min="0" :value="$category->sort_order" hint="Menor aparece primero." />
        </div>
        <x-toggle name="is_active" label="Visible en la tienda" :checked="$category->is_active" />

        <div class="flex gap-2 border-t border-ink-700 pt-5">
            <button class="btn-primary">Guardar</button>
            <a href="{{ route('admin.categories.index') }}" class="btn-ghost">Cancelar</a>
        </div>
    </form>
</x-layouts.admin>
