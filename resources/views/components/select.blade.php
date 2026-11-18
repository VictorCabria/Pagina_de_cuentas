{{-- Lista desplegable con etiqueta, ayuda y error --}}
@props(['name', 'label', 'options' => [], 'value' => null, 'placeholder' => null, 'hint' => null])

<div {{ $attributes->only('class') }}>
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <select id="{{ $name }}" name="{{ $name }}" {{ $attributes->except('class')->merge(['class' => 'form-input']) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $key => $text)
            <option value="{{ $key }}" @selected((string) old($name, $value) === (string) $key)>{{ $text }}</option>
        @endforeach
    </select>
    @if ($hint)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-xs text-rose-400">{{ $message }}</p>
    @enderror
</div>
