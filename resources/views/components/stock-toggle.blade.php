{{-- Botón que alterna Disponible / Agotado con un clic --}}
@props(['action', 'available'])

<form method="POST" action="{{ $action }}">
    @csrf
    @method('PATCH')
    <button title="Clic para marcar como {{ $available ? 'agotado' : 'disponible' }}"
            @class([
                'inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium transition',
                'bg-emerald-500/10 text-emerald-300 hover:bg-emerald-500/20' => $available,
                'bg-rose-500/10 text-rose-300 hover:bg-rose-500/20' => ! $available,
            ])>
        <span @class(['size-1.5 rounded-full', 'bg-emerald-400' => $available, 'bg-rose-400' => ! $available])></span>
        {{ $available ? 'Disponible' : 'Agotado' }}
    </button>
</form>
