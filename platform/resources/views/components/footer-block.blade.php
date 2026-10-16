{{--
    <x-platform::footer-block :block="$block" /> — one block of a footer column (FR-HF-03): the theme's own view
    (partials/footer-blocks/{type}) when it has one, else a module's block (Platform\Website\FooterBlock, FR-LINK-02).
--}}
@props(['block'])
@php($type = (string) ($block['type'] ?? ''))
@if ($type !== '' && view()->exists('theme::partials.footer-blocks.'.$type))
    @include('theme::partials.footer-blocks.'.$type, ['block' => $block])
@else
    {{ app(\Platform\Website\FooterBlocks::class)->render($block, $context ?? null) }}
@endif
