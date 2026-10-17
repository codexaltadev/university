// Alpine.js comes with Livewire (Theme Guide §12): register the theme's components on
// its instance and never load a second copy.
document.documentElement.classList.add('js');

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
});
