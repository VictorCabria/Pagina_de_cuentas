{{-- Logo completo de la tienda (VICON VENTAS). La altura se da con la clase, p. ej. class="h-10". --}}
<picture class="contents">
    <source type="image/webp" srcset="/images/brand/logo.webp 1x, /images/brand/logo@2x.webp 2x">
    <img src="/images/brand/logo.png" srcset="/images/brand/logo@2x.png 2x" width="480" height="137"
         alt="{{ \App\Models\Setting::get('store_name') }}"
         {{ $attributes->merge(['class' => 'w-auto shrink-0 drop-shadow-[0_2px_12px_rgba(99,102,241,0.35)]']) }}>
</picture>
