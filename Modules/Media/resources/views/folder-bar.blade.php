{{--
    Media Library folder explorer (FR-MEDIA-08): breadcrumb, "All files", folder tiles.
    Inline styles on purpose: Filament's compiled CSS has only its own classes.
    $trail list<MediaFolder>, $folders Collection<MediaFolder> (children_count, items_count), $all, $searching, $filesHere
--}}
@php
    $tile = 'display:flex;align-items:center;gap:.6rem;padding:.65rem .8rem;border-radius:.6rem;cursor:pointer;text-align:start;width:100%;border:1px solid rgba(148,163,184,.35);background:rgba(148,163,184,.08);';
    $crumb = 'cursor:pointer;background:none;border:0;padding:0;font:inherit;';
@endphp

<div style="display:flex;flex-direction:column;gap:.9rem;">
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:.4rem;font-size:.875rem;">
        <x-filament::icon icon="heroicon-o-photo" style="width:1.1rem;height:1.1rem;opacity:.7;" />

        <button type="button" wire:click="openFolder(null)" style="{{ $crumb }}{{ ! $all && $trail === [] ? 'font-weight:600;' : 'color:var(--primary-600);' }}">
            {{ __('media::media.title') }}
        </button>

        @foreach ($trail as $i => $crumbFolder)
            <span style="opacity:.5;">›</span>
            <button type="button" wire:click="openFolder({{ $crumbFolder->id }})"
                style="{{ $crumb }}{{ ! $all && $loop->last ? 'font-weight:600;' : 'color:var(--primary-600);' }}">
                {{ $crumbFolder->name }}
            </button>
        @endforeach

        <span style="flex:1;"></span>

        @if ($searching)
            <span style="opacity:.7;">{{ __('media::media.browse.searching_everywhere') }}</span>
        @elseif ($all)
            <span style="font-weight:600;">{{ __('media::media.browse.all_files') }}</span>
        @else
            <span style="opacity:.7;">{{ trans_choice('media::media.browse.files_here', $filesHere, ['count' => $filesHere]) }}</span>
            <span style="opacity:.4;">·</span>
            <button type="button" wire:click="showAll" style="{{ $crumb }}color:var(--primary-600);">
                {{ __('media::media.browse.all_files') }}
            </button>
        @endif
    </div>

    @if ($folders->isNotEmpty() && ! $searching)
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(11rem,1fr));gap:.6rem;">
            @foreach ($folders as $tileFolder)
                <button type="button" wire:click="openFolder({{ $tileFolder->id }})" style="{{ $tile }}" title="{{ $tileFolder->name }}">
                    <x-filament::icon icon="heroicon-s-folder" style="width:1.6rem;height:1.6rem;flex-shrink:0;color:rgb(234 179 8);" />
                    <span style="min-width:0;display:flex;flex-direction:column;">
                        <span style="font-weight:600;font-size:.875rem;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $tileFolder->name }}</span>
                        <span style="font-size:.75rem;opacity:.65;">
                            {{ trans_choice('media::media.browse.files_count', $tileFolder->items_count, ['count' => $tileFolder->items_count]) }}@if ($tileFolder->children_count) · {{ trans_choice('media::media.browse.folders_count', $tileFolder->children_count, ['count' => $tileFolder->children_count]) }}@endif
                        </span>
                    </span>
                </button>
            @endforeach
        </div>
    @endif
</div>
