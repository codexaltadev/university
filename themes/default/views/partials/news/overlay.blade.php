{{-- DIU News card: the picture, a navy fade, the category pill and the title over it. Contract: $card ([title, url, image, category, date, text]) --}}
<a href="{{ $card['url'] }}" class="group relative block h-[250px] rounded-xl overflow-hidden bg-indigo-950 hover:no-underline">
    @if ($card['image'])<img src="{{ $card['image'] }}" alt="" loading="lazy" class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">@endif
    <span class="absolute inset-0 bg-gradient-to-b from-transparent via-indigo-950/50 to-indigo-950"></span>
    <span class="absolute inset-x-0 bottom-0 p-6">
        @if ($card['category'])<span class="inline-block bg-amber-400 text-neutral-900 text-sm px-2.5 py-0.5 rounded-full mb-3">{{ $card['category'] }}</span>@endif
        <span class="block text-white font-bold leading-snug line-clamp-5">{{ $card['title'] }}</span>
    </span>
</a>
