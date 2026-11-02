// University Modern (Theme Guide §12). Alpine.js comes with Livewire: the theme registers its
// components on that instance and never loads a second copy.
document.documentElement.classList.add('js');

// Reduced motion (UI/UX Part B §8): background videos stop on their first frame
const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
if (reduceMotion) {
    document.querySelectorAll('video[autoplay]').forEach((video) => { video.autoplay = false; video.pause(); });
}

// Sections fade in once while scrolling (theme option "animations" puts .reveal on them)
const reveal = () => {
    const items = document.querySelectorAll('.reveal:not(.is-visible)');
    if (reduceMotion || !('IntersectionObserver' in window)) {
        items.forEach((el) => el.classList.add('is-visible'));
        return;
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            }
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    items.forEach((el) => observer.observe(el));
};
document.addEventListener('DOMContentLoaded', reveal);
document.addEventListener('livewire:navigated', reveal);

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Header: one mega panel open at a time, the search panel, the drawer
    Alpine.data('siteHeader', () => ({
        open: null,
        search: false,
        drawer: false,
        toggle(index) { this.open = this.open === index ? null : index; this.search = false; },
        openSearch() {
            this.search = !this.search;
            this.open = null;
            if (this.search) this.$nextTick(() => this.$refs.searchInput?.focus());
        },
        openDrawer() { this.drawer = true; document.body.style.overflow = 'hidden'; },
        close() {
            this.open = null;
            this.search = false;
            if (this.drawer) { this.drawer = false; document.body.style.overflow = ''; }
        },
    }));

    // Hero slides: auto-advance unless the visitor prefers less motion or hovers / focuses
    Alpine.data('heroSlides', (count) => ({
        current: 0,
        count,
        timer: null,
        init() { this.play(); },
        play() {
            if (reduceMotion || this.count < 2) return;
            this.stop();
            this.timer = setInterval(() => this.next(), 7000);
        },
        stop() { clearInterval(this.timer); },
        go(index) { this.current = (index + this.count) % this.count; },
        next() { this.go(this.current + 1); },
    }));
});
