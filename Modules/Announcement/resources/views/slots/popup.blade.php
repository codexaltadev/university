{{--
    Layout slot "popup" (FR-ANW-04/05): the one popup of this page. Shown after load as often as chosen (each visit, once a day,
    once — remembered in the browser); a dialog: Esc or the button closes it, focus stays inside (x-trap), the page behind is inert.
    Contract: $widget
--}}
@php($image = $widget->firstMedia('image'))
<div class="announcement-popup"
     x-data="{ open: false, key: @js($widget->memoryKey()), frequency: @js($widget->frequency),
               seen() { try { const v = localStorage.getItem(this.key); if (!v) return false; if (this.frequency === 'once') return true; if (this.frequency === 'daily') return v === new Date().toDateString(); return false } catch (e) { return false } },
               close() { this.open = false; try { localStorage.setItem(this.key, new Date().toDateString()) } catch (e) {} } }"
     x-init="setTimeout(() => { open = frequency === 'each_visit' ? true : ! seen() }, 600)"
     x-on:keydown.escape.window="open && close()">
    <template x-if="open">
        <div style="position:fixed;inset:0;z-index:1100;background:rgba(0,0,0,.55);display:flex;align-items:center;justify-content:center;padding:16px" x-on:click.self="close()">
            <div role="dialog" aria-modal="true" aria-labelledby="anw-popup-title-{{ $widget->id }}" x-trap.noscroll.inert="open"
                 style="background:var(--color-background, #fff);color:inherit;max-width:560px;width:100%;max-height:90vh;overflow:auto;border-radius:var(--radius, 8px);box-shadow:0 20px 50px rgba(0,0,0,.35);position:relative">
                <button type="button" x-on:click="close()" aria-label="{{ __('announcement::announcement.close') }}"
                        style="position:absolute;top:8px;inset-inline-end:10px;background:rgba(255,255,255,.85);border:0;border-radius:50%;width:34px;height:34px;font-size:1.4rem;cursor:pointer">&times;</button>
                @if ($image)<img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: (string) $widget->title }}" style="width:100%;display:block">@endif
                <div style="padding:20px">
                    <h2 id="anw-popup-title-{{ $widget->id }}" style="margin-top:0">{{ $widget->title ?: __('announcement::announcement.types.popup') }}</h2>
                    @if ($widget->body)<div class="prose">{!! $widget->body !!}</div>@endif
                    @if ($href = $widget->href())
                        <p style="margin-top:16px"><a class="btn" href="{{ $href }}">{{ $widget->button_text ?: __('announcement::announcement.more') }}</a></p>
                    @endif
                </div>
            </div>
        </div>
    </template>
</div>
