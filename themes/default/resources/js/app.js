// Alpine.js comes with Livewire (Theme Guide §12): register the theme's components on
// its instance and never load a second copy.
document.documentElement.classList.add('js');

// DIU RichHtmlContent: page text written for the old site (Bootstrap tables, cards, accordions) shown as
// DIU shows it — in a shadow root with Bootstrap 5.0.2, out of reach of the site's own CSS; collapse
// toggles wired by hand. <diu-rich-html data-css="…/bootstrap-5.0.2.min.css">…</diu-rich-html>
if (!customElements.get('diu-rich-html')) {
    customElements.define('diu-rich-html', class extends HTMLElement {
        connectedCallback() {
            if (this.shadowRoot) return;
            const root = this.attachShadow({ mode: 'open' });
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = this.dataset.css;
            const style = document.createElement('style');
            style.textContent = '.accordion-item, .card { margin-bottom: .5rem; border-radius: .25rem; } img { max-width: 100%; height: auto; }';
            const wrapper = document.createElement('div');
            wrapper.className = 'rich-html-wrapper';
            while (this.firstChild) wrapper.appendChild(this.firstChild);
            root.append(link, style, wrapper);

            wrapper.addEventListener('click', (e) => {
                const trigger = e.target.closest('[data-bs-toggle="collapse"], [data-toggle="collapse"]');
                if (!trigger) return;
                const selector = trigger.getAttribute('data-bs-target') || trigger.getAttribute('data-target')
                    || (trigger.tagName === 'A' && (trigger.getAttribute('href') || '').startsWith('#') ? trigger.getAttribute('href') : null);
                const target = selector ? root.querySelector(selector) : null;
                if (!target) return;
                e.preventDefault();
                const open = !target.classList.contains('show');
                const parent = target.getAttribute('data-bs-parent') || target.getAttribute('data-parent');
                if (open && parent) {
                    root.querySelectorAll(`${parent} .collapse.show`).forEach((other) => other.classList.remove('show'));
                }
                target.classList.toggle('show', open);
                trigger.classList.toggle('collapsed', !open);
                trigger.setAttribute('aria-expanded', String(open));
            });
        }
    });
}

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Header (DIU): search window, mobile menu, utility links fold away once the page scrolls.
    // The header floats over the page; --header-h (its unscrolled height) keeps content below it.
    Alpine.data('siteHeader', () => ({
        search: false,
        drawer: false,
        scrolled: false,

        init() {
            const root = document.documentElement;
            const measure = () => {
                if (!this.scrolled) {
                    root.style.setProperty('--header-h', `${this.$root.offsetHeight}px`);
                }
            };
            const onScroll = () => {
                this.scrolled = window.scrollY > 40;
                this.$root.classList.toggle('is-scrolled', this.scrolled);
            };

            window.addEventListener('scroll', onScroll, { passive: true });
            window.addEventListener('resize', measure, { passive: true });
            onScroll();
            measure();
            document.fonts?.ready.then(measure);
            // Leaving the mobile width closes the menu
            window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => { if (e.matches) this.drawer = false; });
        },

        openSearch() {
            this.drawer = false;
            this.search = true;
            this.$nextTick(() => document.querySelector('#site-search input[type="search"]')?.focus());
        },

        openDrawer() {
            this.search = false;
            this.drawer = true;
        },

        close() {
            this.search = false;
            this.drawer = false;
        },
    }));

    // Carousel (DIU uses Swiper): slides per view by width ({ 0: 1.5, 640: 2, 1024: 3 }), gap in px,
    // optional loop and autoplay (ms, paused on hover / focus / reduced motion), drag or swipe.
    //   <div x-data="carousel({ perView: { 0: 1, 768: 1.3 }, gap: 24, loop: true, autoplay: 6000 })">
    //     <div x-ref="viewport" class="overflow-hidden"><div x-ref="track" class="flex" :style="trackStyle">
    //       <div class="shrink-0" :style="slideStyle">…</div> …
    Alpine.data('carousel', (options = {}) => ({
        perViewMap: options.perView ?? { 0: 1 },
        gap: options.gap ?? 20,
        loop: options.loop ?? false,
        autoplay: options.autoplay ?? 0,
        index: 0,
        count: 0,
        perView: 1,
        width: 0,
        timer: null,
        dragStart: null,
        dragDelta: 0,

        init() {
            this.count = this.$refs.track ? this.$refs.track.children.length : 0;
            const resize = () => {
                this.width = this.$refs.viewport?.clientWidth ?? 0;
                const bp = Object.keys(this.perViewMap).map(Number).sort((a, b) => a - b).filter((w) => window.innerWidth >= w);
                this.perView = this.perViewMap[bp[bp.length - 1] ?? 0] ?? 1;
                this.index = Math.min(this.index, this.maxIndex);
            };
            resize();
            new ResizeObserver(resize).observe(this.$refs.viewport ?? this.$root);
            this.start();
        },

        get maxIndex() { return Math.max(0, Math.ceil(this.count - this.perView)); },
        get slideWidth() { return this.width ? (this.width - this.gap * (Math.ceil(this.perView) - 1)) / this.perView : 0; },
        get slideStyle() { return `width:${this.slideWidth}px;margin-right:${this.gap}px`; },
        get trackStyle() {
            const x = -this.index * (this.slideWidth + this.gap) + this.dragDelta;
            return `transform:translate3d(${x}px,0,0);transition:${this.dragStart === null ? 'transform .6s ease' : 'none'}`;
        },
        get atStart() { return !this.loop && this.index <= 0; },
        get atEnd() { return !this.loop && this.index >= this.maxIndex; },
        isActive(i) { return i === this.index; },

        go(i) {
            this.index = this.loop ? (i + this.maxIndex + 1) % (this.maxIndex + 1) : Math.max(0, Math.min(i, this.maxIndex));
        },
        next() { this.go(this.index + 1); },
        prev() { this.go(this.index - 1); },

        start() {
            if (!this.autoplay || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
            this.stop();
            this.timer = setInterval(() => this.next(), this.autoplay);
        },
        stop() { clearInterval(this.timer); this.timer = null; },

        down(e) { this.dragStart = e.clientX; this.stop(); },
        move(e) { if (this.dragStart !== null) this.dragDelta = e.clientX - this.dragStart; },
        up() {
            if (this.dragStart === null) return;
            if (Math.abs(this.dragDelta) > 40) { this.dragDelta < 0 ? this.next() : this.prev(); }
            this.dragStart = null;
            this.dragDelta = 0;
            this.start();
        },
    }));

    // Number counting up once it scrolls into view (DIU Counter): <span x-data="counter(5600, '+')" x-intersect.once="run()" x-text="text"></span>
    Alpine.data('counter', (value = 0, suffix = '') => ({
        current: 0,
        get text() { return `${Math.floor(this.current).toLocaleString()}${suffix}`; },
        run() {
            if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { this.current = value; return; }
            const start = performance.now();
            const step = (now) => {
                const t = Math.min(1, (now - start) / 2000);
                this.current = value * (1 - Math.pow(1 - t, 3));   // ease-out, 2 s
                if (t < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        },
    }));
});
