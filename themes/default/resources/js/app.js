// Alpine.js comes with Livewire (Theme Guide §12): register the theme's components on
// its instance and never load a second copy.
document.documentElement.classList.add('js');

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Header: search panel, mobile drawer, shadow once the page scrolls (UI/UX Part B §4, §6)
    Alpine.data('siteHeader', () => ({
        search: false,
        drawer: false,
        scrolled: false,

        init() {
            const onScroll = () => { this.scrolled = window.scrollY > 8; };
            window.addEventListener('scroll', onScroll, { passive: true });
            onScroll();
            this.$watch('scrolled', (value) => this.$root.classList.toggle('is-scrolled', value));
            // Leaving the mobile width closes the drawer
            window.matchMedia('(min-width: 1024px)').addEventListener('change', (e) => { if (e.matches) this.drawer = false; });
        },

        toggleSearch() {
            this.search = !this.search;
            if (this.search) {
                this.$nextTick(() => this.$root.querySelector('#site-search input[type="search"]')?.focus());
            }
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
