{{--
    Flipbook (FR-DOC-03): the PDF as a book — Modules/Documents/public/flipbook.js with PDF.js, no server tools.
    Contract: $src (PDF address), $title, $download (download address or null), $page (start page, optional)
--}}
@once
    <script src="{{ module_asset('documents', 'flipbook.js') }}" defer></script>
    <style>
        .flipbook { --fb-bg: color-mix(in srgb, var(--color-secondary, #002652) 92%, black); background: var(--fb-bg); color: #fff; border-radius: var(--radius, 8px); padding: 12px; position: relative; }
        .flipbook:fullscreen { border-radius: 0; display: flex; flex-direction: column; justify-content: center; }
        .flipbook__bar { display: flex; flex-wrap: wrap; gap: 6px; align-items: center; justify-content: center; margin-bottom: 10px; }
        .flipbook__bar button, .flipbook__bar a { background: rgba(255,255,255,.12); color: #fff; border: 0; border-radius: 6px; padding: 6px 10px; cursor: pointer; text-decoration: none; font: inherit; line-height: 1.2 }
        .flipbook__bar button:disabled { opacity: .35; cursor: default }
        .flipbook__bar input { width: 4.5em; padding: 5px 6px; border-radius: 6px; border: 0; color: #111 }
        .flipbook__viewport { overflow: auto; max-height: 82vh; }
        .flipbook__stage { position: relative; margin: 0 auto; perspective: 2600px; }
        .flipbook__page { position: absolute; top: 0; bottom: 0; background: #fff; box-shadow: 0 2px 14px rgba(0,0,0,.45); }
        .flipbook__page img { width: 100%; height: 100%; object-fit: contain; display: block; user-select: none; -webkit-user-drag: none }
        .flipbook__leaf { position: absolute; top: 0; bottom: 0; transform-style: preserve-3d; z-index: 3 }
        .flipbook__leaf--next { transform-origin: left center; animation: fb-next .65s ease-in-out forwards }
        .flipbook__leaf--prev { transform-origin: right center; animation: fb-prev .65s ease-in-out forwards }
        .flipbook__face { position: absolute; inset: 0; backface-visibility: hidden; background: #fff; box-shadow: 0 2px 18px rgba(0,0,0,.5) }
        .flipbook__face img { width: 100%; height: 100%; object-fit: contain; display: block }
        .flipbook__face--back { transform: rotateY(180deg) }
        @keyframes fb-next { to { transform: rotateY(-180deg) } }
        @keyframes fb-prev { to { transform: rotateY(180deg) } }
        .flipbook__spin { width: 36px; height: 36px; border: 3px solid rgba(255,255,255,.25); border-top-color: #fff; border-radius: 50%; animation: fb-spin 1s linear infinite; margin: 0 auto }
        @keyframes fb-spin { to { transform: rotate(360deg) } }
        .flipbook__thumbs { display: grid; grid-auto-flow: column; grid-auto-columns: 84px; gap: 8px; overflow-x: auto; padding: 10px 2px 2px }
        .flipbook__thumbs button { background: #fff; border: 2px solid transparent; padding: 0; cursor: pointer; aspect-ratio: 1 / 1.414 }
        .flipbook__thumbs button[aria-current="true"] { border-color: var(--color-accent, #39B54A) }
        .flipbook .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0, 0, 0, 0); white-space: nowrap; border: 0 }
        @media (prefers-reduced-motion: reduce) { .flipbook__leaf { animation: none !important } }
    </style>
@endonce

<div class="flipbook" role="region" aria-label="{{ $title }}"
     x-data="flipbook({ src: @js($src), page: @js($page ?? 1), pdfjs: @js(module_asset('documents', 'pdfjs/pdf.min.mjs')), worker: @js(module_asset('documents', 'pdfjs/pdf.worker.min.mjs')),
                        labels: { page: @js(__('documents::documents.flipbook.page_of')) } })"
     x-on:keydown="key_($event)" tabindex="0">

    <div class="flipbook__bar" x-show="!loading && !failed">
        <button type="button" x-on:click="first()" x-bind:disabled="current <= 1" aria-label="{{ __('documents::documents.flipbook.first') }}">⏮</button>
        <button type="button" x-on:click="prev()" x-bind:disabled="current <= 1" aria-label="{{ __('documents::documents.flipbook.previous') }}">◀</button>
        <span x-text="label" aria-hidden="true"></span>
        <button type="button" x-on:click="next()" x-bind:disabled="!nextStart()" aria-label="{{ __('documents::documents.flipbook.next') }}">▶</button>
        <button type="button" x-on:click="last()" x-bind:disabled="!nextStart()" aria-label="{{ __('documents::documents.flipbook.last') }}">⏭</button>
        <form x-on:submit.prevent="jumpTo()" style="display:inline-flex;gap:4px">
            <label class="sr-only" for="fb-jump-{{ md5($src) }}">{{ __('documents::documents.flipbook.go_to') }}</label>
            <input id="fb-jump-{{ md5($src) }}" type="number" min="1" x-bind:max="pages" x-model="jump" placeholder="#">
            <button type="submit">{{ __('documents::documents.flipbook.go') }}</button>
        </form>
        <button type="button" x-on:click="zoomOut()" x-bind:disabled="zoom <= 1" aria-label="{{ __('documents::documents.flipbook.zoom_out') }}">−</button>
        <button type="button" x-on:click="zoomIn()" x-bind:disabled="zoom >= 3" aria-label="{{ __('documents::documents.flipbook.zoom_in') }}">+</button>
        <button type="button" x-on:click="toggleThumbs()" x-bind:aria-pressed="thumbs">{{ __('documents::documents.flipbook.pages') }}</button>
        <button type="button" x-on:click="full()" aria-label="{{ __('documents::documents.flipbook.full_screen') }}">⛶</button>
        @if ($download)<a href="{{ $download }}">{{ __('documents::documents.download') }}</a>@endif
    </div>
    <p x-ref="status" class="sr-only" aria-live="polite"></p>

    <template x-if="loading">
        <div style="padding:60px 0;text-align:center">
            <div class="flipbook__spin"></div>
            <p style="margin-top:12px">{{ __('documents::documents.flipbook.loading') }} <span x-show="progress" x-text="progress + '%'"></span></p>
        </div>
    </template>
    <template x-if="failed">
        <p style="padding:40px 0;text-align:center">{{ __('documents::documents.flipbook.failed') }} <a href="{{ $src }}" style="color:#fff;text-decoration:underline" target="_blank" rel="noopener">{{ __('documents::documents.flipbook.open_pdf') }}</a></p>
    </template>
    <noscript><p style="text-align:center"><a href="{{ $src }}" style="color:#fff">{{ __('documents::documents.flipbook.open_pdf') }}</a></p></noscript>

    <div class="flipbook__viewport" x-show="!loading && !failed" x-on:touchstart.passive="touchStart($event)" x-on:touchend="touchEnd($event)">
        <div class="flipbook__stage" x-ref="stage"
             x-bind:style="`width:${100 * zoom}%;aspect-ratio:${(wide ? 2 : 1) / ratio};${zoom === 1 ? `max-width:calc(74vh * ${(wide ? 2 : 1) / ratio})` : ''}`">
            {{-- Wide screen: the left and right pages of the open spread --}}
            <template x-if="wide">
                <div>
                    <template x-if="leftPage()">
                        <div class="flipbook__page" style="left:0;width:50%" x-on:click="prev()">
                            <img x-bind:src="url(leftPage())" x-show="url(leftPage())" x-bind:alt="`{{ __('documents::documents.flipbook.page') }} ${leftPage()}`">
                        </div>
                    </template>
                    <template x-if="rightPage()">
                        <div class="flipbook__page" style="left:50%;width:50%" x-on:click="next()">
                            <img x-bind:src="url(rightPage())" x-show="url(rightPage())" x-bind:alt="`{{ __('documents::documents.flipbook.page') }} ${rightPage()}`">
                        </div>
                    </template>
                    <template x-if="turning">
                        <div class="flipbook__leaf" x-bind:class="'flipbook__leaf--' + turning.dir" x-bind:style="turning.dir === 'next' ? 'left:50%;width:50%' : 'left:0;width:50%'">
                            <div class="flipbook__face"><img x-bind:src="url(turning.front)" alt=""></div>
                            <div class="flipbook__face flipbook__face--back"><template x-if="turning.back"><img x-bind:src="url(turning.back)" alt=""></template></div>
                        </div>
                    </template>
                </div>
            </template>
            {{-- Phone: one page --}}
            <template x-if="!wide">
                <div>
                    <div class="flipbook__page" style="left:0;width:100%">
                        <img x-bind:src="url(singlePage())" x-show="url(singlePage())" x-bind:alt="`{{ __('documents::documents.flipbook.page') }} ${singlePage()}`">
                    </div>
                    <template x-if="turning">
                        <div class="flipbook__leaf" x-bind:class="'flipbook__leaf--' + turning.dir" style="left:0;width:100%">
                            <div class="flipbook__face"><img x-bind:src="url(turning.front)" alt=""></div>
                            <div class="flipbook__face flipbook__face--back"></div>
                        </div>
                    </template>
                </div>
            </template>
        </div>
    </div>

    <template x-if="thumbs">
        <div class="flipbook__thumbs" role="list">
            <template x-for="n in pages" :key="n">
                <button type="button" role="listitem" x-intersect.once="thumb(n)" x-on:click="go(n, true)" x-bind:aria-current="visible().includes(n)" x-bind:aria-label="`{{ __('documents::documents.flipbook.page') }} ${n}`">
                    <img x-bind:src="thumbImages[n]" x-show="thumbImages[n]" alt="" style="width:100%;height:100%;object-fit:contain">
                </button>
            </template>
        </div>
    </template>
</div>
