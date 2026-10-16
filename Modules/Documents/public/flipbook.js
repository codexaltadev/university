/*
 * Flipbook (M-27, FR-DOC-03, DB-05 §12 v1.9): a PDF as a book, with no server tools and no paid
 * software. PDF.js (self-hosted next to this file) draws the pages in the browser; this Alpine
 * component shows two pages side by side on wide screens (the first page alone, as a cover) and
 * one page on phones, turns pages in 3D (instantly with reduced motion), and offers arrows,
 * keyboard, swipe, page number, ?page= links, thumbnails, zoom and full screen. Only the pages
 * around the open one are drawn and kept, so a 300-page prospectus opens fast.
 *
 * Markup: resources/views/partials/flipbook.blade.php — x-data="flipbook({...})".
 */
(() => {
    const CACHE = 14;          // drawn pages kept in memory
    const THUMB_WIDTH = 120;

    const factory = (options) => {
      // PDF.js objects use private fields; Alpine's reactive proxies break them, so the document
      // stays outside the component's reactive data.
      let pdf = null;

      return {
        src: options.src,
        pdfjs: options.pdfjs,
        worker: options.worker,
        labels: options.labels || {},
        ready: false,
        pages: 0,
        current: 1,            // the first page shown (the left one in a spread)
        wide: true,
        zoom: 1,
        ratio: 1.414,          // height / width of page 1, until it is known
        loading: true,
        progress: 0,
        failed: false,
        turning: null,         // { dir, front, back } while a page turns
        thumbs: false,
        jump: '',
        images: new Map(),     // "page@width" → object URL
        thumbImages: {},
        still: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
        touchX: null,

        async init() {
            const media = window.matchMedia('(min-width: 900px)');
            this.wide = media.matches;
            media.addEventListener('change', (e) => { this.wide = e.matches; this.go(this.current); });
            try {
                const lib = await import(this.pdfjs);
                lib.GlobalWorkerOptions.workerSrc = this.worker;
                const task = lib.getDocument({ url: this.src, isEvalSupported: false });
                task.onProgress = ({ loaded, total }) => { if (total) this.progress = Math.round((loaded / total) * 100); };
                pdf = await task.promise;
                this.pages = pdf.numPages;
                this.ready = true;
                const first = await pdf.getPage(1);
                const view = first.getViewport({ scale: 1 });
                this.ratio = view.height / view.width;
                const wanted = parseInt(new URLSearchParams(location.search).get('page') || options.page || '1', 10);
                this.current = this.spreadStart(Math.min(Math.max(wanted || 1, 1), this.pages));
                this.loading = false;
                await this.$nextTick();                    // the stage has its real width now
                await this.drawAround();
                window.addEventListener('resize', () => { clearTimeout(this.resized); this.resized = setTimeout(() => this.drawAround(), 250); });
            } catch (e) {
                console.error(e);
                this.failed = true;
                this.loading = false;
            }
        },

        // ── Which pages are open ────────────────────────────────────────────
        spreadStart(page) {
            if (!this.wide || page === 1) return page;
            return page % 2 === 0 ? page : page - 1;      // spreads: [1], [2,3], [4,5] …
        },
        visible(start = this.current) {
            if (!this.wide) return [start];
            if (start === 1) return [1];
            return [start, start + 1].filter((p) => p <= this.pages);
        },
        nextStart() {
            const step = this.wide ? (this.current === 1 ? 1 : 2) : 1;
            return this.current + step <= this.pages ? this.current + step : null;
        },
        prevStart() {
            if (this.current <= 1) return null;
            return this.spreadStart(this.wide ? Math.max(1, this.current - 2) : this.current - 1) || 1;
        },
        // What lies under a turning leaf: forward, the open left page and the next right page
        leftOf(start) { return this.wide ? (start === 1 ? null : start) : start; },
        rightOf(start) { return this.wide ? (start === 1 ? 1 : (start + 1 <= this.pages ? start + 1 : null)) : null; },
        leftPage() {
            if (!this.turning) return this.leftOf(this.current);
            return this.turning.dir === 'next' ? this.leftOf(this.current) : this.leftOf(this.turning.target);
        },
        rightPage() {
            if (!this.turning) return this.rightOf(this.current);
            return this.turning.dir === 'next' ? this.rightOf(this.turning.target) : this.rightOf(this.current);
        },
        singlePage() { return this.turning ? this.turning.target : this.current; },
        get label() {
            const shown = this.visible();
            const which = shown.length > 1 ? `${shown[0]}–${shown[1]}` : `${shown[0]}`;
            return (this.labels.page || 'Page :n of :total').replace(':n', which).replace(':total', this.pages);
        },

        // ── Drawing ─────────────────────────────────────────────────────────
        pageWidth() {
            const box = this.$refs.stage?.clientWidth || 800;   // already includes the zoom
            const per = this.wide ? box / 2 : box;
            return Math.round(per * Math.min(window.devicePixelRatio || 1, 2));
        },
        key(page, width) { return `${page}@${width}`; },
        url(page) {
            const exact = this.images.get(this.key(page, this.pageWidth()));
            if (exact) return exact;
            for (const [key, url] of this.images) if (url && parseInt(key, 10) === page) return url;   // another size, until this one is drawn

            return null;
        },

        async draw(page, width) {
            const key = this.key(page, width);
            if (this.images.has(key) || page < 1 || page > this.pages) return;
            this.images.set(key, null);                    // drawing
            const p = await pdf.getPage(page);
            const base = p.getViewport({ scale: 1 });
            const viewport = p.getViewport({ scale: width / base.width });
            const canvas = document.createElement('canvas');
            canvas.width = Math.round(viewport.width);
            canvas.height = Math.round(viewport.height);
            await p.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            const blob = await new Promise((done) => canvas.toBlob(done, 'image/webp', 0.9));
            this.images.set(key, URL.createObjectURL(blob));
            this.images = new Map(this.images);            // tell Alpine
            this.forget();
        },
        forget() {
            const keep = new Set(this.visible().concat(this.visible(this.nextStart() || this.current), this.visible(this.prevStart() || this.current)));
            if (this.images.size <= CACHE) return;
            for (const [key, url] of this.images) {
                if (this.images.size <= CACHE) break;
                if (!keep.has(parseInt(key, 10)) && url) { URL.revokeObjectURL(url); this.images.delete(key); }
            }
        },
        async drawAround() {
            const width = this.pageWidth();
            const near = [...this.visible(), ...(this.nextStart() ? this.visible(this.nextStart()) : []), ...(this.prevStart() ? this.visible(this.prevStart()) : [])];
            for (const page of near) await this.draw(page, width);
        },

        // ── Turning pages ───────────────────────────────────────────────────
        async go(page, animate = false) {
            if (!pdf || this.turning) return;
            const target = this.spreadStart(Math.min(Math.max(page, 1), this.pages));
            if (target === this.current && !animate) { this.drawAround(); return; }
            const dir = target > this.current ? 'next' : 'prev';
            if (animate && !this.still && this.zoom === 1) {
                const width = this.pageWidth();
                await Promise.all(this.visible(target).map((p) => this.draw(p, width)));
                const now = this.visible();
                const then = this.visible(target);
                // The leaf that turns: forward, the right page (front) becomes the next left page (back)
                this.turning = dir === 'next'
                    ? { dir, target, front: now[now.length - 1], back: this.wide ? then[0] : null }
                    : { dir, target, front: now[0], back: this.wide ? then[then.length - 1] : null };
                await new Promise((done) => setTimeout(done, 650));
                this.turning = null;
            }
            this.current = target;
            history.replaceState(null, '', this.linkTo(this.current));
            this.$refs.status && (this.$refs.status.textContent = this.label);
            this.drawAround();
        },
        next() { const n = this.nextStart(); if (n) this.go(n, true); },
        prev() { const p = this.prevStart(); if (p) this.go(p, true); },
        first() { this.go(1, true); },
        last() { this.go(this.pages, true); },
        jumpTo() { const n = parseInt(this.jump, 10); if (n) this.go(n, true); this.jump = ''; },
        linkTo(page) {
            const url = new URL(location.href);
            page > 1 ? url.searchParams.set('page', page) : url.searchParams.delete('page');
            return url.pathname + url.search + url.hash;
        },

        // ── Controls ────────────────────────────────────────────────────────
        key_(e) {
            if (e.target.closest('input, textarea, select')) return;
            if (e.key === 'ArrowRight' || e.key === 'PageDown') { e.preventDefault(); this.next(); }
            if (e.key === 'ArrowLeft' || e.key === 'PageUp') { e.preventDefault(); this.prev(); }
            if (e.key === 'Home') this.first();
            if (e.key === 'End') this.last();
        },
        touchStart(e) { this.touchX = this.zoom === 1 ? e.touches[0].clientX : null; },
        touchEnd(e) {
            if (this.touchX === null) return;
            const d = e.changedTouches[0].clientX - this.touchX;
            if (Math.abs(d) > 40) d < 0 ? this.next() : this.prev();
            this.touchX = null;
        },
        zoomIn() { this.zoom = Math.min(3, +(this.zoom + 0.5).toFixed(1)); this.$nextTick(() => this.drawAround()); },
        zoomOut() { this.zoom = Math.max(1, +(this.zoom - 0.5).toFixed(1)); this.$nextTick(() => this.drawAround()); },
        full() {
            const el = this.$root;
            document.fullscreenElement ? document.exitFullscreen() : el.requestFullscreen?.();
            setTimeout(() => this.drawAround(), 300);
        },
        async toggleThumbs() {
            this.thumbs = !this.thumbs;
            if (!this.thumbs) return;
            await this.$nextTick();
            this.$root.querySelector('.flipbook__thumbs')?.scrollIntoView({ block: 'nearest' });
            // The pages around the open one first; the rest as they scroll into view (x-intersect)
            const from = Math.max(1, this.current - 6);
            for (let n = from; n <= Math.min(this.pages, from + 12); n++) await this.thumb(n);
        },
        async thumb(page) {
            if (this.thumbImages[page] !== undefined || !pdf) return;
            this.thumbImages[page] = null;
            const p = await pdf.getPage(page);
            const base = p.getViewport({ scale: 1 });
            const viewport = p.getViewport({ scale: (THUMB_WIDTH * 2) / base.width });
            const canvas = document.createElement('canvas');
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            await p.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
            this.thumbImages[page] = canvas.toDataURL('image/webp', 0.7);
        },
      };
    };

    const register = () => window.Alpine.data('flipbook', factory);
    if (window.Alpine) register();
    else document.addEventListener('alpine:init', register);
})();
