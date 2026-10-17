{{-- DIU's LinkButton: ↗ and a text link whose underline grows on hover. variant: default (white) or primary (navy) --}}
@props(['href' => '#', 'variant' => 'default', 'icon' => true, 'target' => null])
<a href="{{ $href }}" @if ($target) target="{{ $target }}" rel="noopener" @endif {{ $attributes->class([
    'inline-flex items-center cursor-pointer gap-2 text-base md:text-[22px] font-semibold relative transition-colors duration-300 group hover:no-underline',
    $variant === 'primary' ? 'text-secondary hover:text-blue-900' : 'text-white hover:text-gray-300',
]) }}>
    <span class="flex items-center gap-2 relative transition-transform duration-300 group-hover:translate-x-1">
        @if ($icon)
            <span class="group-hover:rotate-45 group-hover:scale-110 transition-transform duration-[400ms]"><x-theme::icon name="arrow-up-right" class="w-6 h-6" /></span>
        @endif
        {{ $slot }}
        <span class="absolute left-0 bottom-0 h-[2px] bg-current w-0 group-hover:w-full transition-all duration-[600ms] ease-out"></span>
    </span>
</a>
