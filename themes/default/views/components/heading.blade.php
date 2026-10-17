{{--
    DIU's Header: a bold title in one of its sizes and colours.
    <x-theme::heading size="base" color="primary">…</x-theme::heading>
    color: title (dark grey), primary (DIU "primary" = navy = our secondary token), white,
    muted (near black), accent (DIU's text-accent has no colour: the section's colour shows).
--}}
@props(['size' => 'base', 'color' => 'title', 'align' => 'left', 'spacing' => 'mb-4', 'tag' => 'div'])
<{{ $tag }} {{ $attributes->class([
    match ($size) {
        'xs' => 'text-xl lg:text-2xl',
        'sm' => 'text-2xl lg:text-3xl',
        'md' => 'text-[24px] lg:leading-12 lg:text-4xl',
        'lg' => 'text-4xl lg:text-5xl',
        'xl' => 'text-[38px] max-sm:leading-10 md:text-5xl lg:text-[50px] 2xl:text-[64px]',
        default => 'lg:leading-16 text-[24px] md:text-3xl lg:text-5xl',
    },
    match ($color) {
        'primary' => 'text-secondary',
        'white' => 'text-white',
        'muted' => 'text-neutral-900',
        'accent' => '',
        default => 'text-neutral-800',
    },
    match ($align) {
        'center' => 'text-center',
        'right' => 'text-right',
        default => 'text-left',
    },
    'font-bold', $spacing,
]) }}>{{ $slot }}</{{ $tag }}>
