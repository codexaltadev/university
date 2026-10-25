{{--
    Layout slot "popup" as DIU's greeting popup (DIUFronend components/UI/GreetingPopupModal): a dark, blurred
    page behind; the picture (a link when there is one); below it the title (when no picture), the text, the
    dates (from / valid till) and "Maybe Later" / "Explore Now". Shown after load as often as chosen (each
    visit, once a day, once — remembered in the browser); Esc, the close button or a click outside closes
    it, focus stays inside. Only while Settings › Announcements has popups on.
    Contract: $widget, $later (Settings: the close button's text)
--}}
<div class="announcement-popup"
     x-data="{ open: false, key: @js($widget->memoryKey()), frequency: @js($widget->frequency),
               seen() { try { const v = localStorage.getItem(this.key); if (!v) return false; if (this.frequency === 'once') return true; if (this.frequency === 'daily') return v === new Date().toDateString(); return false } catch (e) { return false } },
               close() { this.open = false; try { localStorage.setItem(this.key, new Date().toDateString()) } catch (e) {} } }"
     x-init="setTimeout(() => { open = frequency === 'each_visit' ? true : ! seen() }, 600)"
     x-on:keydown.escape.window="open && close()">
    <template x-if="open">
        <div class="fixed inset-0 z-[1100] flex items-center justify-center p-4 sm:p-6 bg-slate-950/80 backdrop-blur-md" x-on:click.self="close()">
            <div role="dialog" aria-modal="true" aria-labelledby="anw-popup-title-{{ $widget->id }}" x-trap.noscroll.inert="open"
                 class="relative w-fit max-w-[min(56rem,calc(100vw-2rem))] max-h-[90vh] flex flex-col rounded-3xl overflow-hidden bg-gradient-to-b from-slate-900 to-slate-950 border border-white/15 shadow-2xl">
                <div class="absolute -top-40 -right-40 w-80 h-80 bg-primary/20 rounded-full blur-3xl pointer-events-none"></div>
                <div class="absolute -bottom-40 -left-40 w-80 h-80 bg-indigo-500/20 rounded-full blur-3xl pointer-events-none"></div>

                <button type="button" x-on:click="close()" aria-label="{{ __('announcement::announcement.close') }}"
                        class="absolute top-3 right-3 sm:top-4 sm:right-4 z-50 w-10 h-10 flex items-center justify-center rounded-full bg-black/60 hover:bg-red-600 border border-white/20 hover:border-red-500 text-white transition-all duration-300 shadow-xl hover:rotate-90 hover:scale-110">
                    <x-theme::icon name="close" class="w-5 h-5" />
                </button>

                @if ($image = $widget->firstMedia('image'))
                    <div class="flex items-center justify-center min-h-0 flex-1 overflow-y-auto">
                        @if ($href = $widget->href())
                            <a href="{{ $href }}" x-on:click="close()" class="block group relative hover:no-underline">
                                <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: (string) $widget->title }}" class="block max-w-full max-h-[70vh] object-contain transition-transform duration-500 group-hover:scale-[1.015]">
                                <span class="absolute inset-0 bg-gradient-to-t from-slate-950/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-end justify-center pb-6">
                                    <span class="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-white text-slate-900 font-extrabold text-sm shadow-2xl">{{ $widget->button_text ?: __('announcement::announcement.explore') }} <x-theme::icon name="arrow-right" class="w-4 h-4 text-primary" /></span>
                                </span>
                            </a>
                        @else
                            <img src="{{ $image->url('large') ?? $image->url() }}" alt="{{ $image->alt_text ?: (string) $widget->title }}" class="block max-w-full max-h-[70vh] object-contain">
                        @endif
                    </div>
                @endif

                <div @class(['relative z-10 p-6 sm:p-8 bg-gradient-to-b from-white to-slate-50 flex flex-col gap-4 flex-shrink-0 overflow-y-auto', 'border-t border-slate-200' => $image, 'sr-only' => $image && ! $widget->body && ! $widget->href()])>
                    <h2 id="anw-popup-title-{{ $widget->id }}" @class(['text-2xl sm:text-3xl font-extrabold tracking-tight text-slate-900 flex items-center gap-2.5 pr-10', 'sr-only' => $image])>
                        <x-theme::icon name="sparkles" class="w-6 h-6 text-primary shrink-0" />{{ $widget->title ?: __('announcement::announcement.types.popup') }}
                    </h2>
                    @if ($widget->body)
                        <div class="prose prose-slate max-w-none text-sm sm:text-base leading-relaxed text-slate-600">{!! $widget->body !!}</div>
                    @endif
                    <div class="flex flex-wrap items-center justify-between gap-4 pt-3 mt-auto border-t border-slate-200/60">
                        <div class="flex flex-wrap items-center gap-2.5 text-xs font-semibold text-slate-500">
                            @if ($widget->active_at)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-slate-100 border border-slate-200"><x-theme::icon name="calendar" class="w-3.5 h-3.5 text-primary" />{{ $widget->active_at->translatedFormat('j M Y') }}</span>
                            @endif
                            @if ($widget->expires_at)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-amber-50 text-amber-700 border border-amber-200/60"><x-theme::icon name="clock" class="w-3.5 h-3.5 text-amber-600" />{{ __('announcement::announcement.valid_till', ['date' => $widget->expires_at->translatedFormat('j M Y')]) }}</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                            <button type="button" x-on:click="close()" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 hover:bg-slate-200/60 transition-colors">{{ $later ?: __('announcement::announcement.close') }}</button>
                            @if ($href = $widget->href())
                                <a href="{{ $href }}" x-on:click="close()" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-secondary via-primary to-secondary text-white font-extrabold text-sm shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all duration-200 hover:no-underline">
                                    {{ $widget->button_text ?: __('announcement::announcement.explore') }} <x-theme::icon name="arrow-right" class="w-4 h-4" />
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
