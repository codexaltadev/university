// Alpine.js comes with Livewire (Theme Guide §12): register the theme's components on
// its instance and never load a second copy. Components are added from 4.2 on.
document.documentElement.classList.add('js');

document.addEventListener('alpine:init', () => {
    // Alpine.data('megaMenu', …) and friends live here.
});
