{{--
    Pictures with a lightbox (FR-GAL-05): ← → and Esc on the keyboard, swipe on a phone; without JavaScript each picture
    links to its large size. Contract: $photos (list of ['item' => MediaItem, 'caption' => ?string]), $layout (masonry / grid / carousel)
--}}
@php($slides = $photos->map(fn (array $p): array => ['src' => $p['item']->url('large') ?? $p['item']->url(), 'alt' => $p['item']->alt_text ?: ($p['caption'] ?? ''), 'caption' => $p['caption'] ?? ''])->values())
@php($wrap = match ($layout) {
    'masonry' => 'columns:240px;column-gap:var(--space-3, 12px)',
    'carousel' => 'display:grid;grid-auto-flow:column;grid-auto-columns:minmax(280px,80%);gap:var(--space-3, 12px);overflow-x:auto;scroll-snap-type:x mandatory',
    default => 'display:grid;gap:var(--space-3, 12px);grid-template-columns:repeat(auto-fill,minmax(200px,1fr))',
})
<div class="gallery-photos gallery-photos--{{ $layout }}"
     x-data="{ open: null, slides: @js($slides), x: 0,
               show(i) { this.open = (i + this.slides.length) % this.slides.length },
               key(e) { if (this.open === null) return; if (e.key === 'Escape') this.open = null; if (e.key === 'ArrowRight') this.show(this.open + 1); if (e.key === 'ArrowLeft') this.show(this.open - 1) } }"
     x-on:keydown.window="key($event)">
    <div style="{{ $wrap }}">
        @foreach ($photos as $i => $photo)
            <figure style="margin:0 0 var(--space-3, 12px);{{ $layout === 'masonry' ? 'break-inside:avoid' : '' }}{{ $layout === 'carousel' ? ';scroll-snap-align:start' : '' }}">
                <a href="{{ $photo['item']->url('large') ?? $photo['item']->url() }}" x-on:click.prevent="show({{ $i }})">
                    <img src="{{ $photo['item']->url('medium') ?? $photo['item']->url() }}" alt="{{ $photo['item']->alt_text ?: ($photo['caption'] ?? '') }}" loading="lazy"
                         style="width:100%;display:block;border-radius:var(--radius-sm, 4px);{{ $layout === 'masonry' ? '' : 'aspect-ratio:4/3;object-fit:cover' }}">
                </a>
                @if ($photo['caption'])<figcaption style="font-size:.85em;margin-top:var(--space-1, 4px)">{{ $photo['caption'] }}</figcaption>@endif
            </figure>
        @endforeach
    </div>

    <template x-if="open !== null">
        <div role="dialog" aria-modal="true" aria-label="{{ __('gallery::gallery.lightbox') }}" x-on:click.self="open = null"
             x-on:touchstart="x = $event.touches[0].clientX" x-on:touchend="let d = $event.changedTouches[0].clientX - x; if (Math.abs(d) > 40) show(open + (d < 0 ? 1 : -1))"
             style="position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.9);display:flex;flex-direction:column;align-items:center;justify-content:center;padding:var(--space-4, 16px)">
            <img x-bind:src="slides[open].src" x-bind:alt="slides[open].alt" style="max-width:100%;max-height:82vh;object-fit:contain">
            <p x-show="slides[open].caption" x-text="slides[open].caption" style="color:#fff;margin-top:var(--space-2, 8px)"></p>
            <p style="color:#ccc;font-size:.85em" x-text="(open + 1) + ' / ' + slides.length"></p>
            <button type="button" x-on:click="open = null" aria-label="{{ __('gallery::gallery.close') }}" style="position:absolute;top:12px;inset-inline-end:16px;font-size:2rem;color:#fff;background:none;border:0">&times;</button>
            <button type="button" x-on:click="show(open - 1)" aria-label="{{ __('gallery::gallery.previous') }}" style="position:absolute;inset-inline-start:8px;top:50%;font-size:2.5rem;color:#fff;background:none;border:0">&#8249;</button>
            <button type="button" x-on:click="show(open + 1)" aria-label="{{ __('gallery::gallery.next') }}" style="position:absolute;inset-inline-end:8px;top:50%;font-size:2.5rem;color:#fff;background:none;border:0">&#8250;</button>
        </div>
    </template>
</div>
