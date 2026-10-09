<x-filament-panels::page>
    <div style="display:grid;gap:1.25rem;grid-template-columns:repeat(auto-fill,minmax(300px,1fr))">
        @foreach ($this->getThemes() as $theme)
            @php($manifest = $theme->manifest())
            <div wire:key="theme-{{ $theme->slug }}" style="border-radius:.75rem;overflow:hidden;background:var(--color-white, #fff);border:{{ $theme->is_active ? '2px solid var(--primary-600, #2563eb)' : '1px solid rgba(0,0,0,.1)' }}" class="dark:bg-gray-900">
                <div style="aspect-ratio:4/3;background:#f1f5f9;overflow:hidden">
                    @if ($manifest->screenshot())
                        <img src="{{ route('theme-manager.screenshot', $theme->slug) }}" alt="" style="width:100%;height:100%;object-fit:cover;display:block" loading="lazy">
                    @endif
                </div>
                <div style="padding:1rem;display:grid;gap:.5rem">
                    <div style="display:flex;align-items:center;gap:.5rem;flex-wrap:wrap">
                        <strong style="font-size:1rem">{{ $theme->name }}</strong>
                        <span style="font-size:.75rem;opacity:.7">v{{ $theme->version }}</span>
                        @if ($theme->is_active)
                            <x-filament::badge color="success">{{ __('thememanager::theme.badges.active') }}</x-filament::badge>
                        @endif
                        @if ($theme->is_bundled)
                            <x-filament::badge color="gray">{{ __('thememanager::theme.badges.bundled') }}</x-filament::badge>
                        @endif
                    </div>
                    @if ($manifest->description())
                        <p style="font-size:.85rem;opacity:.8;margin:0">{{ $manifest->description() }}</p>
                    @endif
                    <p style="font-size:.75rem;opacity:.65;margin:0">
                        {{ __('thememanager::theme.themes.offers', [
                            'headers' => count($manifest->variants('header')),
                            'footers' => count($manifest->variants('footer')),
                            'templates' => count($manifest->pageTemplates()),
                        ]) }}
                    </p>
                    <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.25rem">
                        @unless ($theme->is_active)
                            {{ ($this->activateAction)(['slug' => $theme->slug]) }}
                            {{ ($this->previewAction)(['slug' => $theme->slug]) }}
                            @unless ($theme->is_bundled)
                                {{ ($this->uninstallAction)(['slug' => $theme->slug]) }}
                            @endunless
                        @endunless
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</x-filament-panels::page>
