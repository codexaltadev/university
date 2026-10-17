{{--
    DIU's Button as a link. variant: outline (white text in a turning gradient border, on navy),
    outlineSecondary, primary (blue → navy gradient), outlinePrimary (thin dark border, gradient on hover).
    <x-theme::button href="/events" variant="outlinePrimary" :right-icon="true">Explore All Events</x-theme::button>
--}}
@props(['href' => '#', 'variant' => 'outline', 'rounded' => 'lg', 'padding' => 'px-4 py-2.5', 'size' => 'text-base md:text-lg', 'rightIcon' => false, 'leftIcon' => false, 'target' => null])
<a href="{{ $href }}" @if ($target) target="{{ $target }}" rel="noopener" @endif {{ $attributes->class([
    match ($variant) {
        'outlineSecondary' => 'text-secondary bg-transparent border border-neutral-800 hover:bg-[linear-gradient(40.74deg,color-mix(in_srgb,var(--color-primary)_80%,transparent)_5.6%,color-mix(in_srgb,var(--color-secondary)_80%,transparent)_100%)] hover:text-white',
        'primary' => 'text-white bg-[linear-gradient(98.74deg,color-mix(in_srgb,var(--color-primary)_80%,transparent)_5.6%,color-mix(in_srgb,var(--color-secondary)_80%,transparent)_100%)] border-0 hover:bg-[linear-gradient(40.74deg,color-mix(in_srgb,var(--color-primary)_80%,transparent)_5.6%,color-mix(in_srgb,var(--color-secondary)_80%,transparent)_100%)]',
        'outlinePrimary' => 'bg-transparent border border-neutral-800 hover:bg-gradient-to-r hover:from-primary hover:to-secondary hover:border-transparent hover:text-white',
        default => 'text-white bg-transparent border border-transparent [background:linear-gradient(var(--color-secondary),var(--color-secondary))_padding-box,linear-gradient(var(--angle),white_4.13%,var(--color-primary)_112.21%)_border-box] animate-[rotate_3s_linear_infinite] hover:bg-white',
    },
    match ($rounded) {
        'sm' => 'rounded-sm',
        'md' => 'rounded-md',
        'xl' => 'rounded-xl',
        'full' => 'rounded-full',
        default => 'rounded-lg',
    },
    $padding, $size,
    'relative inline-flex font-medium transition-all duration-300 cursor-pointer hover:scale-[1.02] active:scale-[0.93] hover:no-underline',
]) }}>
    <span class="relative z-10 inline-flex items-center gap-2">
        @if ($leftIcon)<x-theme::icon name="arrow-right" class="h-4 w-4" />@endif
        {{ $slot }}
        @if ($rightIcon)<x-theme::icon name="arrow-right" class="h-4 w-4" />@endif
    </span>
</a>
