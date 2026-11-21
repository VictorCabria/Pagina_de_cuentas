{{-- Datos de acceso de una cuenta, enmascarados. Los reales solo se piden al servidor
     con el ojo (ver/ocultar) o con copiar (va directo al portapapeles, sin mostrarse). --}}
@props(['subscription'])

@php($copyButton = 'grid size-6 shrink-0 place-items-center rounded-md text-mist-400 transition hover:bg-ink-700 hover:text-mist-100')

<div {{ $attributes->merge(['class' => 'group relative w-fit min-w-56 max-w-xs rounded-lg border border-ink-700 bg-ink-900/60 py-2 pr-2 pl-3 text-xs']) }}
     data-secrets data-secrets-url="{{ route('admin.subscriptions.secrets', $subscription) }}">
    <div class="flex items-center gap-2">
        <x-icon name="Email" class="size-3.5 shrink-0 text-mist-400" />
        <span class="min-w-0 flex-1 truncate text-sm text-mist-100" data-secret="email">{{ \App\Support\Mask::email($subscription->account_email) }}</span>
        <button type="button" data-copy="email" class="{{ $copyButton }}" title="Copiar correo" aria-label="Copiar correo">
            <x-icon name="copy" class="size-3.5" data-icon-idle /><x-icon name="check" class="hidden size-3.5 text-emerald-400" data-icon-done />
        </button>
        <button type="button" data-reveal class="{{ $copyButton }}" title="Ver datos" aria-label="Ver datos" aria-pressed="false">
            <x-icon name="eye" class="size-3.5" data-icon-show /><x-icon name="eye-slash" class="hidden size-3.5 text-accent" data-icon-hide />
        </button>
    </div>

    @if ($subscription->account_password)
        <div class="mt-1 flex items-center gap-2">
            <x-icon name="key" class="size-3.5 shrink-0 text-mist-400" />
            <span class="min-w-0 flex-1 truncate font-mono tracking-wider text-mist-300" data-secret="password">{{ \App\Support\Mask::SECRET }}</span>
            <button type="button" data-copy="password" class="{{ $copyButton }}" title="Copiar clave" aria-label="Copiar clave">
                <x-icon name="copy" class="size-3.5" data-icon-idle /><x-icon name="check" class="hidden size-3.5 text-emerald-400" data-icon-done />
            </button>
            <span class="size-6 shrink-0" aria-hidden="true"></span>
        </div>
    @endif

    @if ($subscription->profile_name || $subscription->pin)
        <div class="mt-2 flex flex-wrap gap-1.5 border-t border-ink-700/70 pt-2">
            @if ($subscription->profile_name)
                <span class="inline-flex items-center gap-1 rounded-md bg-accent-soft px-2 py-0.5 text-accent">👤 {{ $subscription->profile_name }}</span>
            @endif
            @if ($subscription->pin)
                <span class="inline-flex items-center gap-1.5 rounded-md bg-ink-700 px-2 py-0.5 text-mist-100">
                    PIN <span class="font-mono font-semibold tracking-wider" data-secret="pin">{{ \App\Support\Mask::SECRET }}</span>
                </span>
            @endif
        </div>
    @endif
</div>
