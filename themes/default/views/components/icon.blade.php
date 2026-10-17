{{--
    <x-theme::icon name="search" class="size-5" /> — inline SVG icon, decorative (aria-hidden);
    the link or button around it carries the text. Outline icons follow currentColor;
    social icons follow DIU's footer (outline; x, tiktok, whatsapp filled).
--}}
@props(['name'])
<svg {{ $attributes->merge(['class' => 'icon', 'width' => 20, 'height' => 20]) }} viewBox="0 0 24 24" aria-hidden="true" focusable="false"
     @if (in_array($name, ['x', 'tiktok', 'whatsapp'], true)) fill="currentColor" @else fill="none" stroke="currentColor" stroke-width="{{ in_array($name, ['video', 'vr', 'facebook', 'instagram', 'youtube', 'linkedin', 'twitter'], true) ? '1.5' : '2' }}" stroke-linecap="round" stroke-linejoin="round" @endif>
    @switch($name)
        @case('search')<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>@break
        @case('menu')<path d="M4 6h16M4 12h16M4 18h16"/>@break
        @case('close')<path d="M18 6 6 18M6 6l12 12"/>@break
        @case('chevron-down')<path d="m6 9 6 6 6-6"/>@break
        @case('chevron-right')<path d="m9 6 6 6-6 6"/>@break
        @case('chevron-up')<path d="m18 15-6-6-6 6"/>@break
        @case('chevron-left')<path d="m15 18-6-6 6-6"/>@break
        @case('play')<path d="M6 3l14 9-14 9V3z"/>@break
        @case('arrow-up-right')<path d="M7 7h10v10M7 17 17 7"/>@break
        @case('grid')<rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/>@break
        @case('video')<path d="m16 13 5.2 3.5a.5.5 0 0 0 .8-.4V7.9a.5.5 0 0 0-.8-.4L16 11"/><rect x="2" y="6" width="14" height="12" rx="2"/>@break
        @case('vr')<path d="M2 9a3 3 0 0 1 3-3h14a3 3 0 0 1 3 3v6a3 3 0 0 1-3 3h-3.5l-2-3h-3l-2 3H5a3 3 0 0 1-3-3z"/><circle cx="7.5" cy="12" r="1.8"/><circle cx="16.5" cy="12" r="1.8"/>@break
        @case('book-open')<path d="M12 7v14M3 18a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1h5a4 4 0 0 1 4 4 4 4 0 0 1 4-4h5a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1h-6a3 3 0 0 0-3 3 3 3 0 0 0-3-3z"/>@break
        @case('graduation-cap')<path d="M21.4 10.9a1 1 0 0 0 0-1.8L12.8 5.2a2 2 0 0 0-1.7 0L2.6 9.1a1 1 0 0 0 0 1.8l8.6 3.9a2 2 0 0 0 1.7 0zM22 10v6M6 12.5V16a6 3 0 0 0 12 0v-3.5"/>@break
        @case('image')<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.1-3.1a2 2 0 0 0-2.8 0L6 21"/>@break
        @case('users')<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>@break
        @case('coffee')<path d="M10 2v2M14 2v2M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 1 1 0 8h-1M6 2v2"/>@break
        @case('languages')<path d="m5 8 6 6M4 14l6-6 2-3M2 5h12M7 2h1M22 22l-5-10-5 10M14 18h6"/>@break
        @case('briefcase')<path d="M16 20V4a2 2 0 0 0-2-2h-4a2 2 0 0 0-2 2v16"/><rect x="2" y="6" width="20" height="14" rx="2"/>@break
        @case('clipboard')<rect x="8" y="2" width="8" height="4" rx="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2M12 11h4M12 16h4M8 11h.01M8 16h.01"/>@break
        @case('building')<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M9 22v-4h6v4M8 6h.01M16 6h.01M12 6h.01M12 10h.01M12 14h.01M16 10h.01M16 14h.01M8 10h.01M8 14h.01"/>@break
        @case('calculator')<rect x="4" y="2" width="16" height="20" rx="2"/><path d="M8 6h8M16 14v4M16 10h.01M12 10h.01M8 10h.01M12 14h.01M8 14h.01M12 18h.01M8 18h.01"/>@break
        @case('email')<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>@break
        @case('pinterest')<circle cx="12" cy="12" r="10"/><path d="M10.5 21.5 12.5 13M9.3 14.6c-.6-.7-.8-1.6-.8-2.6 0-3 2.3-5.2 5.2-5.2 2.6 0 4.3 1.8 4.3 4.2 0 2.9-1.4 5.1-3.5 5.1-1.2 0-2-1-1.7-2.2l.8-3"/>@break
        @case('link')<path d="M10 13a5 5 0 0 0 7.5.5l3-3a5 5 0 0 0-7-7l-1.7 1.7M14 11a5 5 0 0 0-7.5-.5l-3 3a5 5 0 0 0 7 7l1.7-1.7"/>@break
        @case('arrow-left')<path d="M19 12H5M12 19l-7-7 7-7"/>@break
        @case('arrow-right')<path d="M5 12h14M12 5l7 7-7 7"/>@break
        @case('arrow-up')<path d="M12 19V5M5 12l7-7 7 7"/>@break
        @case('phone')<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>@break
        @case('mail')<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/>@break
        @case('map-pin')<path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>@break
        @case('user')<path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>@break
        @case('globe')<circle cx="12" cy="12" r="10"/><path d="M2 12h20M12 2a15 15 0 0 1 0 20M12 2a15 15 0 0 0 0 20"/>@break
        @case('external')<path d="M15 3h6v6M10 14 21 3M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>@break
        @case('facebook')<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>@break
        @case('youtube')<path d="M2.5 17a24 24 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.6 49.6 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24 24 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.6 49.6 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>@break
        @case('linkedin')<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>@break
        @case('x')<path d="M18.2 2.3h3.4l-7.3 8.4 8.6 11.4h-6.8l-5.3-6.9-6 6.9H1.4l7.8-9L1 2.3h6.9l4.8 6.4zm-1.2 17.8h1.9L7.1 4.2H5.1z"/>@break
        @case('instagram')<rect width="20" height="20" x="2" y="2" rx="5"/><path d="M16 11.4A4 4 0 1 1 12.6 8 4 4 0 0 1 16 11.4zM17.5 6.5h.01"/>@break
        @case('twitter')<path d="M22 4s-.7 2.1-2 3.4c1.6 10-9.4 17.3-18 11.6 2.2.1 4.4-.6 6-2C3 15.5.5 9.6 3 5c2.2 2.6 5.6 4.1 9 4-.9-4.2 4-6.6 7-3.8 1.1 0 3-1.2 3-1.2z"/>@break
        @case('tiktok')<path d="M19.6 6.7a4.8 4.8 0 0 1-3.8-4.2V2h-3.4v13.7a2.9 2.9 0 1 1-2-2.8V9.4a6.3 6.3 0 1 0 5.4 6.3V8.7a8.2 8.2 0 0 0 4.8 1.5V6.8z"/>@break
        @case('whatsapp')<path d="M17.5 14.4c-.3-.1-1.8-.9-2-1s-.5-.1-.7.1-.8 1-.9 1.2-.3.2-.6.1a8.2 8.2 0 0 1-2.4-1.5 9 9 0 0 1-1.7-2.1c-.2-.3 0-.5.1-.6l.4-.5.3-.5a.5.5 0 0 0 0-.5L9.1 7c-.2-.6-.5-.5-.7-.5h-.6a1.1 1.1 0 0 0-.8.4 3.4 3.4 0 0 0-1 2.5 5.9 5.9 0 0 0 1.2 3.1 13.5 13.5 0 0 0 5.2 4.6c.7.3 1.3.5 1.7.6a4.2 4.2 0 0 0 1.9.1 3.2 3.2 0 0 0 2.1-1.5 2.6 2.6 0 0 0 .2-1.5c-.1-.1-.3-.2-.6-.4zM12 21.8a9.9 9.9 0 0 1-5-1.4l-.4-.2-3.7 1 1-3.6-.2-.4A9.8 9.8 0 1 1 12 21.8zM20.5 3.5A11.8 11.8 0 0 0 1.9 17.7L.2 24l6.4-1.7A11.8 11.8 0 0 0 24 12a11.7 11.7 0 0 0-3.5-8.5z"/>@break
        @default<circle cx="12" cy="12" r="9"/>
    @endswitch
</svg>
