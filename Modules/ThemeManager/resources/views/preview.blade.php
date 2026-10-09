{{-- Saved website homepage, refreshed after each save (UI/UX Part A §7.11) --}}
<div style="display:grid;gap:.5rem">
    <p style="font-size:.8rem;opacity:.7;margin:0">{{ __('thememanager::theme.help.preview') }}</p>
    <iframe src="{{ url('/') }}?design={{ $version }}" title="{{ __('thememanager::theme.design.preview') }}"
            wire:key="design-preview-{{ $version }}"
            style="width:100%;height:640px;border:1px solid rgba(0,0,0,.12);border-radius:.5rem;background:#fff"></iframe>
</div>
