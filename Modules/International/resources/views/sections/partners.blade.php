{{-- Homepage section "Global Partners" (FR-INTL-09): a logo carousel that moves by itself (stops on hover / reduced motion). Contract: $items (Institution list), $section, $settings; $context from the page --}}
@if ($items->isNotEmpty())
    @php($base = isset($context) && $context->isMicrosite() ? rtrim((string) app(\Platform\Context\MicrositeResolver::class)->urlFor($context->entity), '/') : '')
    <div class="intl-carousel" x-data="{ paused: false, next() { const el = this.$refs.track; if (this.paused || ! el) return; const step = el.firstElementChild ? el.firstElementChild.offsetWidth + 16 : 200; el.scrollLeft + el.clientWidth >= el.scrollWidth - 4 ? el.scrollTo({ left: 0, behavior: 'smooth' }) : el.scrollBy({ left: step, behavior: 'smooth' }); } }"
        x-init="if (! window.matchMedia('(prefers-reduced-motion: reduce)').matches) setInterval(() => next(), 3000)" @mouseenter="paused = true" @mouseleave="paused = false" @focusin="paused = true" @focusout="paused = false">
        <div x-ref="track" style="display:flex;gap:16px;overflow-x:auto;scroll-snap-type:x mandatory;scrollbar-width:none;padding-bottom:4px">
            @foreach ($items as $institution)
                <div style="flex:0 0 180px;scroll-snap-align:start">@include('international::partials.institution', ['institution' => $institution])</div>
            @endforeach
        </div>
    </div>
    <p style="margin-top:var(--space-4, 16px)"><a class="button" href="{{ url($base.'/'.\Modules\International\Support\InternationalWebsite::ROOT.'/partners') }}">{{ __('international::international.see_all') }}</a></p>
@endif
